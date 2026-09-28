@php
    $embed = request()->boolean('embed');
    $ed = $exam->exam_data ?? [];
    $vision = $ed['vision'] ?? [];
    $pg = $ed['pg'] ?? [];
    $st = $ed['st'] ?? [];
    $nct = $ed['nct'] ?? [];
    $oe = $ed['oe'] ?? [];
    $fundus = $ed['fundus'] ?? [];
    $diagnoses = $ed['diagnoses'] ?? [];
    $advices = $ed['advices'] ?? [];
    $complaints = $ed['complaints'] ?? [];
    $kcos = $ed['kcos'] ?? [];
    $ccNames = $complaintMasters->whereIn('id', $complaints)->pluck('complaint')->implode(', ');
    $kcoNames = $kcoMasters->whereIn('id', $kcos)->pluck('kco')->implode(', ');

    $oeFields = [
        'sac' => 'SAC',
        'lid' => 'LID',
        'conj' => 'CONJ',
        'cornea' => 'CORNEA',
        'ac' => 'A/C',
        'iris' => 'IRIS',
        'pupil' => 'PUPIL',
        'lens' => 'LENS',
        'em' => 'E.M.',
        'covertest' => 'COVER TEST',
    ];

    $lensOeVal = function ($oe, $eye) {
        $base = $oe['lens_' . $eye] ?? '';
        if ($base === null || $base === '') {
            return '';
        }

        $pseudo = $oe['pseudophakia_' . $eye] ?? [];
        $extras = array_filter([
            $pseudo['operation_type'] ?? '',
            !empty($pseudo['operation_expense']) ? currency_symbol() . $pseudo['operation_expense'] : '',
            $pseudo['hospital_name'] ?? '',
        ], fn($v) => $v !== '' && $v !== null);

        return $extras ? $base . ' (' . implode(', ', $extras) . ')' : $base;
    };

    // In embed (dashboard modal) mode only the filled parts of the exam are rendered.
    $filled = fn($v) => $v !== null && trim((string) $v) !== '';
    $onlyFilled = fn(array $rows, callable $has) => $embed ? array_filter($rows, $has, ARRAY_FILTER_USE_BOTH) : $rows;

    $vnPairs = [
        'Vn' => [$vision['vn_re'] ?? '', $vision['vn_le'] ?? ''],
        'Pn/Vn' => [$vision['pnvn_re'] ?? '', $vision['pnvn_le'] ?? ''],
        'Nr.Vn' => [$vision['nrvn_re'] ?? '', $vision['nrvn_le'] ?? ''],
    ];
    $vnShow = $onlyFilled($vnPairs, fn($p) => $filled($p[0]) || $filled($p[1]));
    $nctFilled = $filled($nct['iop_re'] ?? null) || $filled($nct['iop_le'] ?? null);

    $historyLine = function (array $row, string $mainKey) use ($filled): string {
        $text = trim((string) ($row[$mainKey] ?? ''));
        if ($filled($row['since'] ?? null)) {
            $text .= ' — ' . $row['since'] . ' ' . ($row['unit'] ?? '');
        }
        if ($filled($row['eye'] ?? null)) {
            $text .= ' (' . $row['eye'] . ')';
        }
        if ($filled($row['comment'] ?? null)) {
            $text .= ' · ' . $row['comment'];
        }

        return trim($text);
    };
    $coLines = collect($ed['co_rows'] ?? [])->filter(fn($r) => $filled($r['complaint'] ?? null))
        ->map(fn($r) => $historyLine($r, 'complaint'))->values();
    $kcoLines = collect($ed['kco_rows'] ?? [])->filter(fn($r) => $filled($r['condition'] ?? null))
        ->map(fn($r) => $historyLine($r, 'condition'))->values();
    $historyFilled = $coLines->isNotEmpty() || $kcoLines->isNotEmpty() || $ccNames || $kcoNames || $filled($ed['allergy'] ?? null);

    $pgRowDefs = ['Dst' => ['ds', 'dc', 'ax', 'vn'], 'Nr' => ['ns', 'nc', 'na', 'near_vn']];
    $pgShowRows = $onlyFilled($pgRowDefs, fn($keys) => collect($keys)
        ->contains(fn($k) => $filled($pg['re'][$k] ?? null) || $filled($pg['le'][$k] ?? null)));
    $hasPg = $embed ? !empty($pgShowRows) : (!empty($pg['re']) || !empty($pg['le']));
    $showVisionBox = !$embed || $historyFilled || $vnShow || $nctFilled || $hasPg;

    $stShowRows = $onlyFilled(['Dst' => ['ds', 'dc', 'ax'], 'Nr' => ['ns', 'nc', 'na']], fn($keys) => collect($keys)
        ->contains(fn($k) => $filled($st['re'][$k] ?? null) || $filled($st['le'][$k] ?? null)));
    $stExtras = $filled($st['add'] ?? null) || $filled($st['lens_type'] ?? null);
    $showStBox = !$embed || $stShowRows || $stExtras;

    $oeVal = fn($key, $eye) => $key === 'lens' ? $lensOeVal($oe, $eye) : ($oe[$key . '_' . $eye] ?? '');
    $oeShow = $onlyFilled($oeFields, fn($label, $key) => $filled($oeVal($key, 're')) || $filled($oeVal($key, 'le')));
    $oeOther = $filled($oe['other_re'] ?? null) || $filled($oe['other_le'] ?? null);
    $showOeBox = !$embed || $oeShow || $oeOther;

    $fundusFields = ['disc' => 'DISC', 'fr' => 'FR', 'macula' => 'MACULA', 'vessels' => 'VESSELS', 'periphery' => 'PERIPHERY'];
    $fundusShow = $onlyFilled($fundusFields, fn($label, $key) => $filled($fundus[$key . '_re'] ?? null) || $filled($fundus[$key . '_le'] ?? null));
    $fundusComment = $filled($fundus['comment'] ?? null);
    $showFundusBox = !$embed || $fundusShow || $fundusComment;

    $nothingFilled = $embed && !$showVisionBox && !$showStBox && !$showOeBox && !$showFundusBox;
    $visibleBoxes = (int) $showVisionBox + (int) $showStBox + (int) $showOeBox + (int) $showFundusBox;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinical HUD — {{ $patient->full_name }}</title>
    <style>
        /* ─── Reset & Base ──────────────────────────────────────── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --brand: #1B4F72;
            --brand-lt: #d6e8f5;
            --muted: #6c757d;
            --border: #1B4F72;
            --row-alt: #f6fafd;
            --radius: 4px;
            --font: 'Inter', 'Segoe UI', Arial, sans-serif;
        }

        html,
        body {
            height: 100%;
        }

        body {
            font-family: var(--font);
            font-size: 12px;
            line-height: 1.35;
            color: #1a1a1a;
            background: #f0f4f8;
        }

        /* ─── No-print toolbar ──────────────────────────────────── */
        .toolbar {
            background: #1B4F72;
            padding: 6px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .toolbar a,
        .toolbar button {
            font-size: 12px;
            color: #fff;
            background: transparent;
            border: 1px solid rgba(255, 255, 255, .4);
            border-radius: 3px;
            padding: 4px 12px;
            cursor: pointer;
            text-decoration: none;
        }

        .toolbar button.print-btn {
            background: rgba(255, 255, 255, .15);
        }

        /* ─── Patient Header ────────────────────────────────────── */
        .pt-header {
            background: #F0F4F8;
            border-bottom: 2px solid var(--brand);
            padding: 6px 14px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 18px;
        }

        .pt-header .pt-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--brand);
        }

        .pt-header .pt-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            background: #fff;
            border: 1px solid #c3d9ee;
            border-radius: 99px;
            padding: 1px 8px;
            color: #333;
        }

        .pt-header .pt-chip span.lbl {
            font-weight: 600;
            color: var(--brand);
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: .04em;
        }

        .pt-header .pt-doctor {
            margin-left: auto;
            font-size: 11px;
            text-align: right;
            color: #444;
        }

        .pt-header .pt-doctor strong {
            color: var(--brand);
            font-size: 12px;
            display: block;
        }

        /* ─── HUD Wrapper ───────────────────────────────────────── */
        .hud-wrapper {
            display: flex;
            height: calc(100vh - 80px);
            overflow: hidden;
        }

        html:has(body.is-embed),
        body.is-embed {
            height: auto;
        }

        body.is-embed .hud-wrapper {
            height: auto;
            overflow: visible;
        }

        body.is-embed .hud-main {
            overflow: visible;
        }

        body.is-embed.is-single .hud-main {
            grid-template-columns: 1fr;
        }

        /* ─── Left Sidebar ──────────────────────────────────────── */
        .hud-sidebar {
            width: 68px;
            background: #fff;
            border-right: 1px solid var(--brand);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 8px 4px;
            gap: 4px;
            overflow-y: auto;
            flex-shrink: 0;
        }

        .hud-sidebar .nav-pill {
            display: block;
            width: 100%;
            text-align: center;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .03em;
            color: var(--brand);
            background: var(--brand-lt);
            border: 1px solid var(--brand);
            border-radius: 3px;
            padding: 5px 2px;
            cursor: pointer;
            text-decoration: none;
            transition: background .1s, color .1s;
        }

        .hud-sidebar .nav-pill:hover,
        .hud-sidebar .nav-pill.active {
            background: var(--brand);
            color: #fff;
        }

        /* ─── Main Content Grid ─────────────────────────────────── */
        .hud-main {
            flex: 1;
            overflow-y: auto;
            padding: 8px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: auto auto;
            gap: 8px;
            align-content: start;
        }

        /* ─── Cards ─────────────────────────────────────────────── */
        .hud-card {
            background: #fff;
            border: 1px solid var(--brand);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .hud-card-title {
            background: var(--brand);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: 4px 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .hud-card-body {
            padding: 6px 8px;
        }

        /* ─── Inline section rows ───────────────────────────────── */
        .info-row {
            display: flex;
            flex-wrap: wrap;
            gap: 2px 16px;
            margin-bottom: 4px;
        }

        .info-row .field {
            display: inline-flex;
            gap: 4px;
            font-size: 11px;
        }

        .info-row .field .lbl {
            font-weight: 700;
            color: var(--brand);
            white-space: nowrap;
        }

        /* ─── V-Notation (< symbol stacked) ──────────────────────
           Shows as:   Vn  <  6/9
                              6/12          */
        .vn-block {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            margin-right: 12px;
        }

        .vn-block .vn-label {
            font-size: 10px;
            font-weight: 700;
            color: var(--brand);
            white-space: nowrap;
        }

        .vn-block .vn-sep {
            font-size: 20px;
            font-weight: 200;
            line-height: .85;
            color: var(--brand);
        }

        .vn-block .vn-values {
            display: flex;
            flex-direction: column;
            font-size: 11px;
            line-height: 1.25;
        }

        .vn-block .vn-values .re {
            font-weight: 600;
        }

        .vn-block .vn-values .le {
            color: #444;
        }

        /* ─── Dense tables ──────────────────────────────────────── */
        .hud-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .hud-table th {
            background: var(--brand);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: 3px 5px;
            border: 1px solid var(--brand);
            white-space: nowrap;
        }

        .hud-table td {
            padding: 2px 5px;
            border: 1px solid #c3d9ee;
            vertical-align: middle;
        }

        .hud-table tr:nth-child(even) td {
            background: var(--row-alt);
        }

        .hud-table .row-lbl {
            font-weight: 700;
            color: var(--brand);
            background: #eaf3fb !important;
            white-space: nowrap;
            font-size: 10px;
        }

        .hud-table .subhead td {
            background: #deeaf5 !important;
            font-weight: 700;
            font-size: 10px;
            color: var(--brand);
            text-align: center;
            letter-spacing: .04em;
        }

        /* ─── Diagnosis / Advice tags ───────────────────────────── */
        .tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 3px;
            margin-top: 3px;
        }

        .tag {
            background: var(--brand-lt);
            border: 1px solid #a8ccec;
            border-radius: 99px;
            padding: 1px 8px;
            font-size: 10px;
            color: #1a3c58;
        }

        /* ─── Medicine table ────────────────────────────────────── */
        .rx-table th {
            background: #0e3a57;
        }

        .rx-table td {
            font-size: 11px;
        }

        .rx-table .bold {
            font-weight: 600;
        }

        /* ─── Footer strip ──────────────────────────────────────── */
        .hud-footer {
            border-top: 1px solid var(--brand);
            padding: 4px 8px;
            font-size: 10px;
            color: var(--muted);
            display: flex;
            gap: 16px;
        }

        .hud-footer strong {
            color: var(--brand);
        }

        /* ─── Misc helpers ──────────────────────────────────────── */
        .dash {
            color: #aaa;
        }

        {!! axis_chip_css() !!}

        .section-sep {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--brand);
            border-bottom: 1px solid var(--brand-lt);
            margin: 5px 0 3px;
        }

        /* ─── Print ─────────────────────────────────────────────── */
        @media print {

            .toolbar,
            .hud-sidebar {
                display: none !important;
            }

            body {
                background: #fff;
                font-size: 11px;
            }

            .hud-wrapper {
                height: auto;
                overflow: visible;
            }

            .hud-main {
                display: grid;
                grid-template-columns: 1fr 1fr;
                overflow: visible;
                height: auto;
            }

            .hud-card {
                page-break-inside: avoid;
            }
        }
    </style>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,600,700&display=swap" rel="stylesheet">
</head>

<body @class(['is-embed' => $embed, 'is-single' => $embed && $visibleBoxes <= 1]) data-boxes="{{ $visibleBoxes }}">

    @unless($embed)
    {{-- ── No-print Toolbar ── --}}
    <div class="toolbar no-print">
        <button class="print-btn" onclick="window.print()">&#128438; Print</button>
        <a href="{{ route('hospital.patients.show', ['slug' => $slug, 'patient' => $patient->id]) }}">&#8592;
            Patient</a>
        <a href="{{ route('hospital.exam.primary.show', ['slug' => $slug, 'id' => $patient->id]) }}">&#9998; Edit
            Exam</a>
        <span style="margin-left:auto;font-size:11px;color:rgba(255,255,255,.7)">
            Clinical HUD — {{ $patient->full_name }} — {{ now()->format('d M Y') }}
        </span>
    </div>

    {{-- Print header with logo (print only) --}}
    <div class="print-header d-none d-print-block"
        style="padding:12px 24px;border-bottom:2px solid #1B4F72;margin-bottom:8px;">
        <div style="display:flex;align-items:center;gap:12px;">
            <div
                style="width:72px;height:72px;border-radius:12px;background:#F8FAFC;border:1px solid #E5E7EB;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                @if(hospital_print_logo_url())
                    <img src="{{ hospital_print_logo_url() }}" alt="{{ hospital_name() }} logo"
                        style="width:100%;height:100%;object-fit:contain;padding:8px;">
                @else
                    <span style="font-size:28px;color:#1B4F72">👁</span>
                @endif
            </div>
            <div style="flex:1;text-align:left">
                <div style="font-weight:800;color:#1B4F72;font-size:18px">{{ hospital_name() }}</div>
                <div style="font-size:12px;color:#6b7280">{{ hospital_full_address() ?? '' }}</div>
            </div>
        </div>
    </div>

    {{-- ── Patient Header ── --}}
    <div class="pt-header">
        <span class="pt-name">{{ $patient->full_name }}</span>
        <span class="pt-chip"><span class="lbl">MRD</span> {{ $patient->patient_code }}</span>
        <span class="pt-chip"><span class="lbl">Age</span> {{ $patient->age ?? '—' }}y</span>
        <span class="pt-chip"><span class="lbl">Sex</span> {{ ucfirst($patient->gender ?? '—') }}</span>
        @if($patient->contact_no)
            <span class="pt-chip"><span class="lbl">Ph</span> {{ $patient->contact_no }}</span>
        @endif
        @if($patient->appointment_date)
            <span class="pt-chip"><span class="lbl">OPD</span> {{ $patient->appointment_date->format('d M Y') }}</span>
        @endif
        <div class="pt-doctor">
            <strong>{{ $exam->doctor?->name ?? '—' }}</strong>
            {{ $exam->examined_at?->format('d M Y') ?? now()->format('d M Y') }}
        </div>
    </div>
    @endunless

    {{-- ── HUD wrapper ── --}}
    <div class="hud-wrapper">

        @unless($embed)
        {{-- ─── Left Sidebar ─────────────────────────────────────── --}}
        <nav class="hud-sidebar no-print">
            <a href="#sec-history" class="nav-pill">C/O</a>
            <a href="#sec-vision" class="nav-pill">VN</a>
            <a href="#sec-pg" class="nav-pill">PG</a>
            <a href="#sec-st" class="nav-pill">ST</a>
            <a href="#sec-nct" class="nav-pill">NCT</a>
            <a href="#sec-oe" class="nav-pill">O/E</a>
            <a href="#sec-fundus" class="nav-pill">FND</a>
            <a href="#sec-dx" class="nav-pill">Dx</a>
            <a href="#sec-rx" class="nav-pill">Rx</a>
        </nav>
        @endunless

        {{-- ─── Main Grid ─────────────────────────────────────────── --}}
        <div class="hud-main">

            {{-- ════════════════════════════════════
            BOX 1 — History, Vision & PG
            ════════════════════════════════════ --}}
            @if($nothingFilled)
                <div class="hud-card" style="grid-column:1 / -1">
                    <div class="hud-card-body" style="padding:24px;text-align:center;color:var(--muted);font-size:13px">
                        No primary examination details recorded yet.
                    </div>
                </div>
            @endif

            @if($showVisionBox)
            <div class="hud-card" id="sec-history">
                <div class="hud-card-title">&#9673; History &amp; Vision</div>
                <div class="hud-card-body">

                    {{-- Chief Complaint + KCO --}}
                    @if($historyFilled)
                    <div class="info-row">
                        @if($coLines->isNotEmpty())
                            <span class="field">
                                <span class="lbl">C/O:</span>
                                <span>{{ $coLines->implode(', ') }}</span>
                            </span>
                        @elseif($ccNames)
                            <span class="field">
                                <span class="lbl">C/O:</span>
                                <span>{{ $ccNames }}
                                    @if(!empty($ed['complaint_duration']))
                                        <em style="color:#888;font-size:10px">({{ $ed['complaint_duration'] }})</em>
                                    @endif
                                </span>
                            </span>
                        @endif
                        @if($kcoLines->isNotEmpty())
                            <span class="field">
                                <span class="lbl">K/C/O:</span>
                                <span>{{ $kcoLines->implode(', ') }}</span>
                            </span>
                        @elseif($kcoNames)
                            <span class="field">
                                <span class="lbl">K/C/O:</span>
                                <span>{{ $kcoNames }}</span>
                            </span>
                        @endif
                        @if(!empty($ed['allergy']))
                            <span class="field">
                                <span class="lbl">Allergy:</span>
                                <span style="color:#c0392b">{{ $ed['allergy'] }}</span>
                            </span>
                        @endif
                    </div>
                    @endif

                    @if(!$embed || $vnShow || $nctFilled)
                    <div class="section-sep" id="sec-vision">Vision (VN)</div>
                    {{-- V-Notation row: Vn, Pn/Vn, NrVn --}}
                    <div style="display:flex;flex-wrap:wrap;gap:4px 0;align-items:flex-end;margin-bottom:4px">

                        @foreach($vnShow as $lbl => [$re, $le])
                            <div class="vn-block">
                                <span class="vn-label">{{ $lbl }}</span>
                                <span class="vn-sep">&lt;</span>
                                <div class="vn-values">
                                    <span class="re">{{ $re ?: '—' }}</span>
                                    <span class="le">{{ $le ?: '—' }}</span>
                                </div>
                            </div>
                        @endforeach

                        @if(!empty($nct['iop_re']) || !empty($nct['iop_le']))
                            <div class="vn-block" id="sec-nct">
                                <span class="vn-label">NCT</span>
                                <span class="vn-sep">&lt;</span>
                                <div class="vn-values">
                                    <span class="re">{{ $nct['iop_re'] ?: '—' }} mmHg</span>
                                    <span class="le">{{ $nct['iop_le'] ?: '—' }} mmHg</span>
                                </div>
                            </div>
                        @endif
                    </div>
                    @endif

                    {{-- PG (Plus Glass) --}}
                    @if($hasPg)
                        <div class="section-sep" id="sec-pg">Plus Glass (PG)</div>
                        <table class="hud-table" style="margin-bottom:4px">
                            <thead>
                                <tr>
                                    <th rowspan="2" style="width:28px">D</th>
                                    <th colspan="4" style="text-align:center">RIGHT EYE (OD)</th>
                                    <th colspan="4" style="text-align:center">LEFT EYE (OS)</th>
                                </tr>
                                <tr>
                                    @foreach(['re', 'le'] as $eye)
                                        <th>SPH</th>
                                        <th>CYL</th>
                                        <th>AXIS</th>
                                        <th>VN</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pgShowRows as $rowLbl => [$sphKey, $cylKey, $axKey, $vnKey])
                                    <tr>
                                        <td class="row-lbl">{{ $rowLbl }}</td>
                                        @foreach(['re', 'le'] as $eye)
                                            <td>{!! $filled($pg[$eye][$sphKey] ?? null) ? e($pg[$eye][$sphKey]) : '<span class="dash">—</span>' !!}</td>
                                            <td>{!! $filled($pg[$eye][$cylKey] ?? null) ? e($pg[$eye][$cylKey]) : '<span class="dash">—</span>' !!}</td>
                                            <td>{!! axis_chip($pg[$eye][$axKey] ?? '', '<span class="dash">—</span>') !!}</td>
                                            <td>{!! $filled($pg[$eye][$vnKey] ?? null) ? e($pg[$eye][$vnKey]) : '<span class="dash">—</span>' !!}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    @unless($embed)
                    {{-- Diagnosis --}}
                    @if(!empty($diagnoses) && $diagnosisMasters->isNotEmpty())
                        <div class="section-sep" id="sec-dx">Diagnosis</div>
                        <div class="tag-list">
                            @foreach($diagnosisMasters as $d)
                                @if(in_array($d->id, $diagnoses))
                                    <span class="tag">{{ $d->diagnosis }}</span>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    {{-- Advice --}}
                    @if(!empty($advices) && $adviceMasters->isNotEmpty())
                        <div class="section-sep">Advice</div>
                        <div class="tag-list">
                            @foreach($adviceMasters as $a)
                                @if(in_array($a->id, $advices))
                                    <span class="tag">{{ $a->advice }}</span>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($ed['special_advice']))
                        <div style="font-size:11px;color:#555;font-style:italic;margin-top:3px">
                            {{ $ed['special_advice'] }}
                        </div>
                    @endif

                    {{-- Follow-up --}}
                    @if(!empty($ed['followup_date']) || !empty($ed['followup_duration']))
                        <div style="margin-top:5px;font-size:11px">
                            <strong style="color:var(--brand)">Follow-up:</strong>
                            @if(!empty($ed['followup_date']))
                                {{ \Carbon\Carbon::parse($ed['followup_date'])->format('d M Y') }}
                            @endif
                            @if(!empty($ed['followup_duration']))
                                ({{ $ed['followup_duration'] }})
                            @endif
                        </div>
                    @endif
                    @endunless

                </div>
            </div>
            @endif

            {{-- ════════════════════════════════════
            BOX 2 — ST (Subjective Trial)
            ════════════════════════════════════ --}}
            @if($showStBox)
            <div class="hud-card" id="sec-st">
                <div class="hud-card-title">&#9675; ST — Final Glass Prescription</div>
                <div class="hud-card-body">

                    @if($stShowRows)
                    <table class="hud-table">
                        <thead>
                            <tr>
                                <th rowspan="2" style="width:28px">D</th>
                                <th colspan="3" style="text-align:center">RIGHT EYE (OD)</th>
                                <th colspan="3" style="text-align:center">LEFT EYE (OS)</th>
                            </tr>
                            <tr>
                                <th>SPH</th>
                                <th>CYL</th>
                                <th>AXIS</th>
                                <th>SPH</th>
                                <th>CYL</th>
                                <th>AXIS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stShowRows as $rowLbl => [$sphKey, $cylKey, $axKey])
                                <tr>
                                    <td class="row-lbl">{{ $rowLbl }}</td>
                                    @foreach(['re', 'le'] as $eye)
                                        <td>{!! !empty($st[$eye][$sphKey]) ? e($st[$eye][$sphKey]) : '<span class="dash">—</span>' !!}</td>
                                        <td>{!! !empty($st[$eye][$cylKey]) ? e($st[$eye][$cylKey]) : '<span class="dash">—</span>' !!}</td>
                                        <td>{!! axis_chip($st[$eye][$axKey] ?? '', '<span class="dash">—</span>') !!}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif

                    @if(!empty($st['add']) || !empty($st['lens_type']))
                        <div class="info-row" style="margin-top:4px">
                            @if(!empty($st['add']))
                                <span class="field"><span class="lbl">ADD:</span> {{ $st['add'] }}</span>
                            @endif
                            @if(!empty($st['lens_type']))
                                <span class="field"><span class="lbl">Lens:</span> {{ $st['lens_type'] }}</span>
                            @endif
                        </div>
                    @endif

                    {{-- Rx (Medicines) --}}
                    @if(!$embed && $exam->prescriptions->isNotEmpty())
                        <div class="section-sep" id="sec-rx">Rx — Medicines</div>
                        <table class="hud-table rx-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Medicine Name</th>
                                    <th>Dosage</th>
                                    <th>Days</th>
                                    <th>QTY</th>
                                    <th>Mode</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($exam->prescriptions as $i => $rx)
                                    <tr>
                                        <td style="text-align:center;width:20px">{{ $i + 1 }}</td>
                                        <td class="bold">
                                            {{ $rx->medicine?->brand_name ?: ($rx->medicine?->name ?? '—') }}
                                            @if($rx->medicine?->name && $rx->medicine?->brand_name)
                                                <br><span
                                                    style="font-size:9.5px;color:#666;font-weight:400">{{ $rx->medicine->name }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $rx->dosage?->dosage ?? '—' }}</td>
                                        <td style="white-space:nowrap">{{ $rx->duration ? $rx->duration . ' Days' : '—' }}</td>
                                        <td>{{ $rx->quantity ?? '—' }}</td>
                                        <td>{{ $rx->route?->name ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                </div>
            </div>
            @endif

            {{-- ════════════════════════════════════
            BOX 3 — O/E (On Examination)
            ════════════════════════════════════ --}}
            @if($showOeBox)
            <div class="hud-card" id="sec-oe">
                <div class="hud-card-title">&#9679; O/E </div>
                <div class="hud-card-body" style="padding:0">
                    <table class="hud-table">
                        <thead>
                            <tr>
                                <th style="width:90px">O/E</th>
                                <th>RIGHT EYE (OD)</th>
                                <th>LEFT EYE (OS)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($oeShow as $key => $label)
                                <tr>
                                    <td class="row-lbl">{{ $label }}</td>
                                    <td>
                                        @php $val = $oeVal($key, 're'); @endphp
                                        @if($val) {{ $val }} @else <span class="dash">—</span> @endif
                                    </td>
                                    <td>
                                        @php $val = $oeVal($key, 'le'); @endphp
                                        @if($val) {{ $val }} @else <span class="dash">—</span> @endif
                                    </td>
                                </tr>
                            @endforeach
                            @if(!empty($oe['other_re']) || !empty($oe['other_le']))
                                <tr>
                                    <td class="row-lbl">OTHER</td>
                                    <td>{!! filled($oe['other_re'] ?? null) ? e($oe['other_re']) : '<span class="dash">—</span>' !!}</td>
                                    <td>{!! filled($oe['other_le'] ?? null) ? e($oe['other_le']) : '<span class="dash">—</span>' !!}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- ════════════════════════════════════
            BOX 4 — Fundus
            ════════════════════════════════════ --}}
            @if($showFundusBox)
            <div class="hud-card" id="sec-fundus">
                <div class="hud-card-title">&#9632; Fundus Examination</div>
                <div class="hud-card-body" style="padding:0">
                    <table class="hud-table">
                        <thead>
                            <tr>
                                <th style="width:90px">Fundus</th>
                                <th>RIGHT EYE (OD)</th>
                                <th>LEFT EYE (OS)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fundusShow as $key => $label)
                                <tr>
                                    <td class="row-lbl">{{ $label }}</td>
                                    <td>
                                        @php $val = $fundus[$key . '_re'] ?? ''; @endphp
                                        @if($val) {{ $val }} @else <span class="dash">—</span> @endif
                                    </td>
                                    <td>
                                        @php $val = $fundus[$key . '_le'] ?? ''; @endphp
                                        @if($val) {{ $val }} @else <span class="dash">—</span> @endif
                                    </td>
                                </tr>
                            @endforeach
                            @if(!empty($fundus['comment']))
                                <tr>
                                    <td class="row-lbl">COMMENT</td>
                                    <td colspan="2" style="font-style:italic;color:#444">{{ $fundus['comment'] }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>

                    {{-- NCT IOP spillover (if not shown in vision row) --}}
                    @if(!$embed && (!empty($nct['iop_re']) || !empty($nct['iop_le'])))
                        <div style="padding:5px 8px;border-top:1px solid #c3d9ee;font-size:11px">
                            <span class="lbl" style="font-weight:700;color:var(--brand)">IOP / NCT: </span>
                            &nbsp;OD: <strong>{{ $nct['iop_re'] ?? '—' }}</strong> mmHg
                            &nbsp;&nbsp;OS: <strong>{{ $nct['iop_le'] ?? '—' }}</strong> mmHg
                        </div>
                    @endif

                </div>
            </div>
            @endif

        </div>{{-- /.hud-main --}}
    </div>{{-- /.hud-wrapper --}}

    <script>
        // Smooth scroll for sidebar pills
        document.querySelectorAll('.hud-sidebar .nav-pill').forEach(function (pill) {
            pill.addEventListener('click', function (e) {
                e.preventDefault();
                var target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                document.querySelectorAll('.hud-sidebar .nav-pill').forEach(function (p) { p.classList.remove('active'); });
                this.classList.add('active');
            });
        });
    </script>

</body>

</html>