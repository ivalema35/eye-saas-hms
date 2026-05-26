@php
    $ed       = $exam->exam_data ?? [];
    $vision   = $ed['vision']   ?? [];
    $pg       = $ed['pg']       ?? [];
    $st       = $ed['st']       ?? [];
    $nct      = $ed['nct']      ?? [];
    $oe       = $ed['oe']       ?? [];
    $fundus   = $ed['fundus']   ?? [];
    $diagnoses = $ed['diagnoses'] ?? [];
    $advices   = $ed['advices']   ?? [];
    $complaints = $ed['complaints'] ?? [];
    $kcos       = $ed['kcos'] ?? [];
    $ccNames  = $complaintMasters->whereIn('id', $complaints)->pluck('complaint')->implode(', ');
    $kcoNames = $kcoMasters->whereIn('id', $kcos)->pluck('kco')->implode(', ');

    $oeFields = [
        'sac'       => 'SAC',
        'lid'       => 'LID',
        'conj'      => 'CONJ',
        'cornea'    => 'CORNEA',
        'ac'        => 'A/C',
        'iris'      => 'IRIS',
        'pupil'     => 'PUPIL',
        'lens'      => 'LENS',
        'em'        => 'E.M.',
        'covertest' => 'COVER TEST',
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinical HUD — {{ $patient->full_name }}</title>
    <style>
        /* ─── Reset & Base ──────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand:    #1B4F72;
            --brand-lt: #d6e8f5;
            --muted:    #6c757d;
            --border:   #1B4F72;
            --row-alt:  #f6fafd;
            --radius:   4px;
            --font:     'Inter', 'Segoe UI', Arial, sans-serif;
        }

        html, body { height: 100%; }

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
        .toolbar a, .toolbar button {
            font-size: 12px;
            color: #fff;
            background: transparent;
            border: 1px solid rgba(255,255,255,.4);
            border-radius: 3px;
            padding: 4px 12px;
            cursor: pointer;
            text-decoration: none;
        }
        .toolbar button.print-btn { background: rgba(255,255,255,.15); }

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
        .pt-header .pt-doctor strong { color: var(--brand); font-size: 12px; display: block; }

        /* ─── HUD Wrapper ───────────────────────────────────────── */
        .hud-wrapper {
            display: flex;
            height: calc(100vh - 80px);
            overflow: hidden;
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
        .hud-card-body { padding: 6px 8px; }

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
        .vn-block .vn-values .re { font-weight: 600; }
        .vn-block .vn-values .le { color: #444; }

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
        .hud-table tr:nth-child(even) td { background: var(--row-alt); }
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
        .tag-list { display: flex; flex-wrap: wrap; gap: 3px; margin-top: 3px; }
        .tag {
            background: var(--brand-lt);
            border: 1px solid #a8ccec;
            border-radius: 99px;
            padding: 1px 8px;
            font-size: 10px;
            color: #1a3c58;
        }

        /* ─── Medicine table ────────────────────────────────────── */
        .rx-table th { background: #0e3a57; }
        .rx-table td { font-size: 11px; }
        .rx-table .bold { font-weight: 600; }

        /* ─── Footer strip ──────────────────────────────────────── */
        .hud-footer {
            border-top: 1px solid var(--brand);
            padding: 4px 8px;
            font-size: 10px;
            color: var(--muted);
            display: flex;
            gap: 16px;
        }
        .hud-footer strong { color: var(--brand); }

        /* ─── Misc helpers ──────────────────────────────────────── */
        .dash { color: #aaa; }
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
            .toolbar, .hud-sidebar { display: none !important; }
            body { background: #fff; font-size: 11px; }
            .hud-wrapper { height: auto; overflow: visible; }
            .hud-main { display: grid; grid-template-columns: 1fr 1fr; overflow: visible; height: auto; }
            .hud-card { page-break-inside: avoid; }
        }
    </style>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,600,700&display=swap" rel="stylesheet">
</head>
<body>

{{-- ── No-print Toolbar ── --}}
<div class="toolbar no-print">
    <button class="print-btn" onclick="window.print()">&#128438; Print</button>
    <a href="{{ route('hospital.patients.show', ['slug' => $slug, 'patient' => $patient->id]) }}">&#8592; Patient</a>
    <a href="{{ route('hospital.exam.primary.show', ['slug' => $slug, 'id' => $patient->id]) }}">&#9998; Edit Exam</a>
    <span style="margin-left:auto;font-size:11px;color:rgba(255,255,255,.7)">
        Clinical HUD — {{ $patient->full_name }} — {{ now()->format('d M Y') }}
    </span>
</div>

{{-- Print header with logo (print only) --}}
<div class="print-header d-none d-print-block" style="padding:12px 24px;border-bottom:2px solid #1B4F72;margin-bottom:8px;">
    <div style="display:flex;align-items:center;gap:12px;">
        <div style="width:72px;height:72px;border-radius:12px;background:#F8FAFC;border:1px solid #E5E7EB;display:flex;align-items:center;justify-content:center;overflow:hidden;">
            @if(hospital_logo_url())
                <img src="{{ hospital_logo_url() }}" alt="{{ hospital_name() }} logo" style="width:100%;height:100%;object-fit:contain;padding:8px;">
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

{{-- ── HUD wrapper ── --}}
<div class="hud-wrapper">

    {{-- ─── Left Sidebar ─────────────────────────────────────── --}}
    <nav class="hud-sidebar no-print">
        <a href="#sec-history"  class="nav-pill">C/O</a>
        <a href="#sec-vision"   class="nav-pill">VN</a>
        <a href="#sec-pg"       class="nav-pill">PG</a>
        <a href="#sec-st"       class="nav-pill">ST</a>
        <a href="#sec-nct"      class="nav-pill">NCT</a>
        <a href="#sec-oe"       class="nav-pill">O/E</a>
        <a href="#sec-fundus"   class="nav-pill">FND</a>
        <a href="#sec-dx"       class="nav-pill">Dx</a>
        <a href="#sec-rx"       class="nav-pill">Rx</a>
    </nav>

    {{-- ─── Main Grid ─────────────────────────────────────────── --}}
    <div class="hud-main">

        {{-- ════════════════════════════════════
             BOX 1 — History, Vision & PG
             ════════════════════════════════════ --}}
        <div class="hud-card" id="sec-history">
            <div class="hud-card-title">&#9673; History &amp; Vision</div>
            <div class="hud-card-body">

                {{-- Chief Complaint + KCO --}}
                <div class="info-row">
                    @if($ccNames)
                    <span class="field">
                        <span class="lbl">C/O:</span>
                        <span>{{ $ccNames }}
                            @if(!empty($ed['complaint_duration']))
                                <em style="color:#888;font-size:10px">({{ $ed['complaint_duration'] }})</em>
                            @endif
                        </span>
                    </span>
                    @endif
                    @if($kcoNames)
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

                <div class="section-sep" id="sec-vision">Vision (VN)</div>
                {{-- V-Notation row: Vn, Pn/Vn, NrVn --}}
                <div style="display:flex;flex-wrap:wrap;gap:4px 0;align-items:flex-end;margin-bottom:4px">

                    @php
                        $vnPairs = [
                            'Vn'       => [$vision['vn_re']   ?? '',  $vision['vn_le']   ?? ''],
                            'Pn/Vn'    => [$vision['pnvn_re'] ?? '',  $vision['pnvn_le'] ?? ''],
                            'Nr.Vn'    => [$vision['nrvn_re'] ?? '',  $vision['nrvn_le'] ?? ''],
                        ];
                    @endphp

                    @foreach($vnPairs as $lbl => [$re, $le])
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

                {{-- PG (Plus Glass) --}}
                @php
                    $hasPg = !empty($pg['re']) || !empty($pg['le']);
                    $pgKeys = ['vn' => 'VN', 'add' => 'ADD', 'near_vn' => 'NrVn'];
                @endphp
                @if($hasPg)
                <div class="section-sep" id="sec-pg">Plus Glass (PG)</div>
                <table class="hud-table" style="margin-bottom:4px">
                    <thead>
                        <tr>
                            <th style="width:55px"></th>
                            @foreach($pgKeys as $k => $lbl)<th>{{ $lbl }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(['re' => 'RE', 'le' => 'LE'] as $eye => $elbl)
                        <tr>
                            <td class="row-lbl">{{ $elbl }}</td>
                            @foreach(array_keys($pgKeys) as $k)
                                <td>{{ $pg[$eye][$k] ?? '<span class="dash">—</span>' }}</td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif

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

            </div>
        </div>

        {{-- ════════════════════════════════════
             BOX 2 — ST (Subjective Trial)
             ════════════════════════════════════ --}}
        <div class="hud-card" id="sec-st">
            <div class="hud-card-title">&#9675; ST — Final Glass Prescription</div>
            <div class="hud-card-body">

                <table class="hud-table">
                    <thead>
                        <tr>
                            <th rowspan="2" style="width:28px">D</th>
                            <th colspan="3" style="text-align:center">RIGHT EYE (OD)</th>
                            <th colspan="3" style="text-align:center">LEFT EYE (OS)</th>
                        </tr>
                        <tr>
                            <th>SPH</th><th>CYL</th><th>AXIS</th>
                            <th>SPH</th><th>CYL</th><th>AXIS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="row-lbl">Dst</td>
                            <td>{{ $st['re']['ds'] ?? '<span class="dash">—</span>' }}</td>
                            <td>{{ $st['re']['dc'] ?? '<span class="dash">—</span>' }}</td>
                            <td>{{ $st['re']['ax'] ?? '<span class="dash">—</span>' }}</td>
                            <td>{{ $st['le']['ds'] ?? '<span class="dash">—</span>' }}</td>
                            <td>{{ $st['le']['dc'] ?? '<span class="dash">—</span>' }}</td>
                            <td>{{ $st['le']['ax'] ?? '<span class="dash">—</span>' }}</td>
                        </tr>
                        <tr>
                            <td class="row-lbl">Nr</td>
                            <td>{{ $st['re']['ns'] ?? '<span class="dash">—</span>' }}</td>
                            <td>{{ $st['re']['nc'] ?? '<span class="dash">—</span>' }}</td>
                            <td>{{ $st['re']['na'] ?? '<span class="dash">—</span>' }}</td>
                            <td>{{ $st['le']['ns'] ?? '<span class="dash">—</span>' }}</td>
                            <td>{{ $st['le']['nc'] ?? '<span class="dash">—</span>' }}</td>
                            <td>{{ $st['le']['na'] ?? '<span class="dash">—</span>' }}</td>
                        </tr>
                    </tbody>
                </table>

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
                @if($exam->prescriptions->isNotEmpty())
                <div class="section-sep" id="sec-rx">Rx — Medicines</div>
                <table class="hud-table rx-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Medicine</th>
                            <th>Dose</th>
                            <th>Dur.</th>
                            <th>Eye</th>
                            <th>Instr.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($exam->prescriptions as $i => $rx)
                        <tr>
                            <td style="text-align:center;width:20px">{{ $i + 1 }}</td>
                            <td class="bold">
                                {{ $rx->medicine?->brand_name ?: ($rx->medicine?->name ?? '—') }}
                                @if($rx->medicine?->name && $rx->medicine?->brand_name)
                                    <br><span style="font-size:9.5px;color:#666;font-weight:400">{{ $rx->medicine->name }}</span>
                                @endif
                            </td>
                            <td>{{ $rx->dosage?->dosage ?? '—' }}</td>
                            <td style="white-space:nowrap">{{ $rx->duration ? $rx->duration.'d' : '—' }}</td>
                            <td>{{ $rx->eye ?? '—' }}</td>
                            <td>{{ $rx->instructions ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif

            </div>
        </div>

        {{-- ════════════════════════════════════
             BOX 3 — O/E (On Examination)
             ════════════════════════════════════ --}}
        <div class="hud-card" id="sec-oe">
            <div class="hud-card-title">&#9679; O/E — Ocular Examination</div>
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
                        @foreach($oeFields as $key => $label)
                        <tr>
                            <td class="row-lbl">{{ $label }}</td>
                            <td>
                                @php $val = $oe[$key.'_re'] ?? ''; @endphp
                                @if($val) {{ $val }} @else <span class="dash">—</span> @endif
                            </td>
                            <td>
                                @php $val = $oe[$key.'_le'] ?? ''; @endphp
                                @if($val) {{ $val }} @else <span class="dash">—</span> @endif
                            </td>
                        </tr>
                        @endforeach
                        @if(!empty($oe['other_re']) || !empty($oe['other_le']))
                        <tr>
                            <td class="row-lbl">OTHER</td>
                            <td>{{ $oe['other_re'] ?? '<span class="dash">—</span>' }}</td>
                            <td>{{ $oe['other_le'] ?? '<span class="dash">—</span>' }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ════════════════════════════════════
             BOX 4 — Fundus
             ════════════════════════════════════ --}}
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
                        @foreach(['disc' => 'DISC', 'fr' => 'FR', 'macula' => 'MACULA', 'vessels' => 'VESSELS', 'periphery' => 'PERIPHERY'] as $key => $label)
                        <tr>
                            <td class="row-lbl">{{ $label }}</td>
                            <td>
                                @php $val = $fundus[$key.'_re'] ?? ''; @endphp
                                @if($val) {{ $val }} @else <span class="dash">—</span> @endif
                            </td>
                            <td>
                                @php $val = $fundus[$key.'_le'] ?? ''; @endphp
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
                @if(!empty($nct['iop_re']) || !empty($nct['iop_le']))
                <div style="padding:5px 8px;border-top:1px solid #c3d9ee;font-size:11px">
                    <span class="lbl" style="font-weight:700;color:var(--brand)">IOP / NCT: </span>
                    &nbsp;OD: <strong>{{ $nct['iop_re'] ?? '—' }}</strong> mmHg
                    &nbsp;&nbsp;OS: <strong>{{ $nct['iop_le'] ?? '—' }}</strong> mmHg
                </div>
                @endif

            </div>
        </div>

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
