<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription — {{ $patient->full_name }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            background: #fff;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 10mm 12mm 10mm;
        }

        /* ── Header ── */
        .rx-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            border-bottom: 2px solid #1B3A5C;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .rx-logo {
            width: 64px;
            height: 64px;
            border-radius: 10px;
            background: #F8FAFC;
            border: 1px solid #E5E7EB;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-right: 10px;
        }

        .rx-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 6px;
        }

        .rx-logo span {
            font-size: 26px;
            color: #1B3A5C;
        }

        .rx-header-hospital h1 {
            font-size: 18px;
            font-weight: 700;
            color: #1B3A5C;
        }

        .rx-header-hospital p {
            font-size: 10px;
            color: #555;
            margin-top: 2px;
        }

        .rx-header-doctor {
            text-align: right;
            font-size: 10px;
            color: #444;
        }

        .rx-header-doctor strong {
            font-size: 12px;
            color: #1B3A5C;
            display: block;
        }

        /* ── Patient Info ── */
        .rx-patient {
            background: #eef3fb;
            border: 1px solid #c7d9f5;
            border-radius: 6px;
            padding: 6px 12px;
            margin-bottom: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
        }

        .rx-patient-field label {
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #666;
            display: block;
        }

        .rx-patient-field span {
            font-weight: 700;
            font-size: 12px;
        }

        /* ── Two-column grid ── */
        .rx-grid {
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }

        .rx-col {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        /* ── Section boxes ── */
        .rx-box {
            border: 1px solid #bfcfe8;
            border-radius: 4px;
            overflow: hidden;
        }

        .rx-box-title {
            background: #1B3A5C;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            padding: 4px 8px;
        }

        .rx-box-body {
            padding: 6px 8px;
        }

        /* ── C/O & K/C/O tables ── */
        .cv-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #1B3A5C;
            margin-bottom: 2px;
            margin-top: 4px;
        }

        .cv-label:first-child {
            margin-top: 0;
        }

        table.cv-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-bottom: 3px;
        }

        table.cv-table thead tr {
            background: #eef3fb;
        }

        table.cv-table th {
            padding: 2px 5px;
            text-align: center;
            border: 1px solid #c7d9f5;
            font-weight: 600;
            font-size: 9.5px;
            color: #1B3A5C;
        }

        table.cv-table td {
            padding: 2px 5px;
            border: 1px solid #c7d9f5;
            text-align: center;
        }

        table.cv-table td:first-child {
            text-align: left;
        }

        /* ── H/O chips ── */
        .hno-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 3px;
            margin: 3px 0;
        }

        .hno-chip {
            background: rgba(27, 58, 92, .08);
            border: 1px solid rgba(27, 58, 92, .18);
            color: #1B3A5C;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
            padding: 1px 8px;
        }

        /* ── Vision summary line ── */
        .vn-line {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
            font-size: 10.5px;
            border-top: 1px solid #dde3ea;
            padding-top: 4px;
            margin-top: 4px;
        }

        .vn-item {
            display: inline-flex;
            align-items: center;
            gap: 2px;
        }

        .vn-item strong {
            color: #1B3A5C;
        }

        /* ── PG table (inside History & Vision) ── */
        .pg-eye-header {
            background: #1B3A5C;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .05em;
            padding: 3px 6px;
            text-align: center;
        }

        table.pg-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }

        table.pg-table th {
            background: #eef3fb;
            padding: 2px 4px;
            text-align: center;
            border: 1px solid #c7d9f5;
            font-weight: 600;
            font-size: 9.5px;
            color: #1B3A5C;
        }

        table.pg-table td {
            padding: 2px 4px;
            border: 1px solid #c7d9f5;
            text-align: center;
        }

        table.pg-table td.row-label {
            font-weight: 700;
            font-size: 9.5px;
            background: #f5f8ff;
            color: #475569;
            text-align: center;
        }

        /* ── Subjective Testing (ST) ── */
        table.st-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }

        table.st-table .eye-header {
            background: #1B3A5C;
            color: #fff;
            text-align: center;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 4px;
        }

        table.st-table th {
            background: #eef3fb;
            padding: 2px 4px;
            text-align: center;
            border: 1px solid #c7d9f5;
            font-weight: 600;
            font-size: 9.5px;
            color: #1B3A5C;
        }

        table.st-table td {
            padding: 2px 4px;
            border: 1px solid #c7d9f5;
            text-align: center;
        }

        table.st-table td.row-label {
            font-weight: 700;
            font-size: 9.5px;
            color: #475569;
            background: #f5f8ff;
            width: 18px;
        }

        .st-add {
            font-size: 10.5px;
            margin-top: 3px;
            color: #1B3A5C;
        }

        /* ── O/E table ── */
        table.oe-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }

        table.oe-table thead tr {
            background: #eef3fb;
        }

        table.oe-table th {
            padding: 3px 6px;
            border: 1px solid #c7d9f5;
            font-weight: 700;
            font-size: 9.5px;
            color: #1B3A5C;
            text-align: center;
        }

        table.oe-table th:first-child {
            text-align: left;
        }

        table.oe-table td {
            padding: 2px 6px;
            border: 1px solid #c7d9f5;
            text-align: center;
        }

        table.oe-table td:first-child {
            text-align: left;
            font-weight: 700;
            font-size: 10px;
            background: #f5f8ff;
            padding-left: 8px;
        }

        table.oe-table tr:nth-child(even) td {
            background: #f8faff;
        }

        table.oe-table tr:nth-child(even) td:first-child {
            background: #f0f4f8;
        }

        .oe-val-hi {
            color: #c0392b;
            font-weight: 600;
        }

        .oe-val-hib {
            color: #1B3A5C;
            font-weight: 600;
        }

        /* ── Fundus table ── */
        table.fundus-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }

        table.fundus-table thead tr {
            background: #eef3fb;
        }

        table.fundus-table th {
            padding: 3px 6px;
            border: 1px solid #c7d9f5;
            font-weight: 700;
            font-size: 9.5px;
            color: #1B3A5C;
            text-align: center;
        }

        table.fundus-table td {
            padding: 2px 6px;
            border: 1px solid #c7d9f5;
            text-align: center;
        }

        table.fundus-table td:first-child {
            text-align: left;
            font-weight: 700;
            font-size: 10px;
            background: #f5f8ff;
            padding-left: 8px;
        }

        /* ── Diagnosis & RX ── */
        .dx-line {
            font-size: 11px;
            margin-bottom: 2px;
        }

        .rx-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 3px;
            margin-top: 3px;
        }

        .rx-tag {
            background: #eef3fb;
            border: 1px solid #c7d9f5;
            border-radius: 99px;
            padding: 1px 9px;
            font-size: 10px;
        }

        .rx-sub-title {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #1B3A5C;
            margin: 5px 0 2px;
        }

        /* ── Medicine table ── */
        table.rx-medicines {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-top: 3px;
        }

        table.rx-medicines th {
            background: #1B3A5C;
            color: #fff;
            padding: 3px 6px;
            font-size: 9.5px;
            font-weight: 600;
            text-align: left;
        }

        table.rx-medicines td {
            padding: 3px 6px;
            border-bottom: 1px solid #e0e8f5;
            vertical-align: top;
        }

        table.rx-medicines tr:nth-child(even) td {
            background: #f8faff;
        }

        .med-brand {
            font-size: 9px;
            color: #666;
        }

        /* ── Footer ── */
        .rx-footer {
            border-top: 1px solid #c7d9f5;
            margin-top: 12px;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .rx-followup {
            font-size: 10.5px;
            color: #333;
        }

        .rx-followup strong {
            color: #1B3A5C;
        }

        .rx-signature {
            text-align: right;
            font-size: 10.5px;
        }

        .rx-signature .sig-line {
            border-top: 1px solid #333;
            width: 130px;
            margin: 28px 0 3px auto;
        }

        @media print {
            body {
                background: #fff;
            }

            .no-print {
                display: none !important;
            }

            .page {
                padding: 6mm 10mm;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>

<body>

    {{-- Print / Back controls --}}
    <div class="no-print" style="background:#f0f0f0;padding:8px 12mm;display:flex;gap:12px;align-items:center">
        <button onclick="window.print()"
            style="background:#1B3A5C;color:#fff;border:none;padding:6px 18px;border-radius:5px;cursor:pointer;font-size:13px">
            Print
        </button>
        <a href="javascript:history.back()" style="color:#1B3A5C;font-size:13px;text-decoration:none">← Back</a>
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

    <div class="page">

        {{-- ── Header ── --}}
        @php $letterPad = hospital_setting('letter_pad', 'unavailable'); @endphp

        @if($letterPad === 'available')
            <div class="rx-header">
                <div></div>
                <div class="rx-header-doctor">
                    <strong>{{ $exam->doctor?->name ?? '—' }}</strong>
                    @if($exam->doctor?->designation ?? null)
                        <span style="display:block">{{ $exam->doctor->designation }}</span>
                    @endif
                    <span style="display:block;margin-top:2px;color:#888">Date:
                        {{ $exam->examined_at?->format('d M Y') ?? now()->format('d M Y') }}</span>
                </div>
            </div>
        @else
            <div class="rx-header">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="rx-logo">
                        @if(hospital_logo_url())
                            <img src="{{ hospital_logo_url() }}" alt="{{ hospital_name() }} logo">
                        @else
                            <span>👁</span>
                        @endif
                    </div>
                    <div class="rx-header-hospital">
                        <h1>{{ hospital_name() }}</h1>
                        <p>
                            @if(hospital_full_address()) {{ hospital_full_address() }} @endif
                            @if(hospital_contact_number()) &nbsp;|&nbsp; {{ hospital_contact_number() }} @endif
                            @if(hospital_official_email()) &nbsp;|&nbsp; {{ hospital_official_email() }} @endif
                        </p>
                    </div>
                </div>
                <div class="rx-header-doctor">
                    <strong>{{ $exam->doctor?->name ?? '—' }}</strong>
                    @if($exam->doctor?->designation ?? null)
                        <span style="display:block">{{ $exam->doctor->designation }}</span>
                    @endif
                    <span style="display:block;margin-top:2px;color:#888">Date:
                        {{ $exam->examined_at?->format('d M Y') ?? now()->format('d M Y') }}</span>
                </div>
            </div>
        @endif

        {{-- ── Patient Info ── --}}
        <div class="rx-patient">
            <div class="rx-patient-field">
                <label>Patient</label>
                <span>{{ $patient->full_name }}</span>
            </div>
            <div class="rx-patient-field">
                <label>MRD</label>
                <span>{{ $patient->patient_code }}</span>
            </div>
            <div class="rx-patient-field">
                <label>Age / Gender</label>
                <span>{{ $patient->age }}y / {{ ucfirst($patient->gender) }}</span>
            </div>
            @if($patient->contact_no)
                <div class="rx-patient-field">
                    <label>Contact</label>
                    <span>{{ $patient->contact_no }}</span>
                </div>
            @endif
            <div class="rx-patient-field">
                <label>Visit Date</label>
                <span>{{ $patient->appointment_date?->format('d M Y') ?? $exam->examined_at?->format('d M Y') ?? '—' }}</span>
            </div>
        </div>

        {{-- ── Two-column grid ── --}}
        <div class="rx-grid">

            {{-- ══ LEFT COLUMN ══ --}}
            <div class="rx-col">

                {{-- Box 1: History & Vision --}}
                <div class="rx-box">
                    <div class="rx-box-title">History &amp; Vision</div>
                    <div class="rx-box-body">

                        {{-- C/O table --}}
                        @if(!empty($coRows))
                            <div class="cv-label">C/O</div>
                            <table class="cv-table">
                                <thead>
                                    <tr>
                                        <th style="text-align:left">Complaint</th>
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
                        @endif

                        {{-- K/C/O table --}}
                        @if(!empty($kcoRows))
                            <div class="cv-label">K/C/O</div>
                            <table class="cv-table">
                                <thead>
                                    <tr>
                                        <th style="text-align:left">Condition</th>
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
                        @endif

                        {{-- H/O chips --}}
                        @if(!empty($hnoList))
                            <div class="cv-label">H/O</div>
                            <div class="hno-chips">
                                @foreach($hnoList as $hv)
                                    <span class="hno-chip">{{ $hv }}</span>
                                @endforeach
                            </div>
                        @endif

                        {{-- Vision summary line --}}
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
                            <div class="vn-line">
                                @if($hasVision)
                                    <span class="vn-item"><strong>Vn</strong> &lt; {{ $vnRe }}&nbsp;/&nbsp;{{ $vnLe }}</span>
                                    <span class="vn-item"><strong>PH</strong> &lt; {{ $phRe }}&nbsp;/&nbsp;{{ $phLe }}</span>
                                    <span class="vn-item"><strong>NrVn</strong> &lt; {{ $nrRe }}&nbsp;/&nbsp;{{ $nrLe }}</span>
                                @endif
                                @if($iopRe !== $dash || $iopLe !== $dash)
                                    <span class="vn-item"><strong>IOP:</strong>&nbsp;{{ $iopRe }}/{{ $iopLe }}</span>
                                @endif
                            </div>
                        @endif

                        {{-- PG (Objective glass prescription) --}}
                        @php
                            $hasPg = !empty($pg['re']['ds']) || !empty($pg['le']['ds']) ||
                                !empty($pg['re']['ns']) || !empty($pg['le']['ns']);
                        @endphp
                        @if($hasPg)
                            <div style="margin-top:5px">
                                @foreach(['re' => 'RIGHT EYE (RE)', 'le' => 'LEFT EYE (LE)'] as $eye => $eLabel)
                                    @if(!empty($pg[$eye]['ds']) || !empty($pg[$eye]['ns']))
                                        <div class="pg-eye-header" style="margin-top:{{ $loop->first ? '0' : '4px' }}">{{ $eLabel }}
                                        </div>
                                        <table class="pg-table">
                                            <thead>
                                                <tr>
                                                    <th style="width:22px"></th>
                                                    <th>SPH</th>
                                                    <th>CYL</th>
                                                    <th>AXIS</th>
                                                    <th>VN</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="row-label">D</td>
                                                    <td>{{ rxVal($pg[$eye]['ds'] ?? '') }}</td>
                                                    <td>{{ rxVal($pg[$eye]['dc'] ?? '') }}</td>
                                                    <td>{{ rxVal($pg[$eye]['ax'] ?? '') }}</td>
                                                    <td>{{ rxVal($pg[$eye]['vn'] ?? '') }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="row-label">N</td>
                                                    <td>{{ rxVal($pg[$eye]['ns'] ?? '') }}</td>
                                                    <td>{{ rxVal($pg[$eye]['nc'] ?? '') }}</td>
                                                    <td>{{ rxVal($pg[$eye]['na'] ?? '') }}</td>
                                                    <td>{{ rxVal($pg[$eye]['near_vn'] ?? '') }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                    </div>{{-- /rx-box-body --}}
                </div>{{-- /Box 1 --}}

                {{-- Box 2: Subjective Testing (ST) + Diagnosis & Rx --}}
                <div class="rx-box">
                    <div class="rx-box-title">ST</div>
                    <div class="rx-box-body">

                        @php
                            $hasSt = !empty($st['re']['ds']) || !empty($st['le']['ds']) ||
                                !empty($st['re']['ns']) || !empty($st['le']['ns']);
                        @endphp

                        @if($hasSt)
                            <table class="st-table">
                                <thead>
                                    <tr>
                                        <td style="border:none;"></td>
                                        <th colspan="4" class="eye-header">RIGHT EYE (RE)</th>
                                        <th colspan="4" class="eye-header">LEFT EYE (LE)</th>
                                    </tr>
                                    <tr>
                                        <td style="border:none;width:16px;"></td>
                                        <th>SPH</th>
                                        <th>CYL</th>
                                        <th>AXIS</th>
                                        <th>VN</th>
                                        <th>SPH</th>
                                        <th>CYL</th>
                                        <th>AXIS</th>
                                        <th>VN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="row-label">D</td>
                                        <td>{{ rxVal($st['re']['ds'] ?? '') }}</td>
                                        <td>{{ rxVal($st['re']['dc'] ?? '') }}</td>
                                        <td>{{ rxVal($st['re']['ax'] ?? '') }}</td>
                                        <td>{{ rxVal($st['re']['vn'] ?? '') }}</td>
                                        <td>{{ rxVal($st['le']['ds'] ?? '') }}</td>
                                        <td>{{ rxVal($st['le']['dc'] ?? '') }}</td>
                                        <td>{{ rxVal($st['le']['ax'] ?? '') }}</td>
                                        <td>{{ rxVal($st['le']['vn'] ?? '') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="row-label">N</td>
                                        <td>{{ rxVal($st['re']['ns'] ?? '') }}</td>
                                        <td>{{ rxVal($st['re']['nc'] ?? '') }}</td>
                                        <td>{{ rxVal($st['re']['na'] ?? '') }}</td>
                                        <td>{{ rxVal($st['re']['near_vn'] ?? '') }}</td>
                                        <td>{{ rxVal($st['le']['ns'] ?? '') }}</td>
                                        <td>{{ rxVal($st['le']['nc'] ?? '') }}</td>
                                        <td>{{ rxVal($st['le']['na'] ?? '') }}</td>
                                        <td>{{ rxVal($st['le']['near_vn'] ?? '') }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            @php
                                $addRe = $st['re']['add'] ?? '';
                                $addLe = $st['le']['add'] ?? '';
                            @endphp
                            @if($addRe || $addLe || !empty($st['add']))
                                <div class="st-add">
                                    <strong>ADD</strong>&nbsp;
                                    RE: {{ $addRe ?: ($st['add'] ?? $dash) }}&nbsp;&nbsp;
                                    LE: {{ $addLe ?: $dash }}
                                    @if(!empty($st['lens_type']))
                                        &nbsp;|&nbsp; {{ $st['lens_type'] }}
                                    @endif
                                </div>
                            @endif
                        @endif

                    </div>

                    {{-- Diagnosis & Rx sub-section --}}
                    <div class="rx-box-title" style="margin-top:0">Diagnosis &amp; Rx</div>
                    <div class="rx-box-body">

                        @php
                            $dx = $ed['diagnoses'] ?? [];
                            $dxNames = $diagnosisMasters->filter(fn($d) => in_array($d->id, $dx));
                            $dilate = $ed['dilate'] ?? ($ed['dilation'] ?? null);
                        @endphp

                        <div class="dx-line">
                            <strong>Dx:</strong>
                            @if($dxNames->isNotEmpty())
                                {{ $dxNames->pluck('diagnosis')->implode(', ') }}
                            @else
                                -
                            @endif
                            &nbsp;&nbsp;
                            <strong>Dilate:</strong> {{ $dilate ? ucfirst($dilate) : 'No' }}
                        </div>

                        {{-- Advice --}}
                        @if(!empty($advices) && $adviceMasters->isNotEmpty())
                            @php $advNames = $adviceMasters->filter(fn($a) => in_array($a->id, $advices)); @endphp
                            @if($advNames->isNotEmpty())
                                <div class="rx-sub-title">Advice</div>
                                <div class="rx-tags">
                                    @foreach($advNames as $a)
                                        <span class="rx-tag">{{ $a->advice }}</span>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        @if(!empty($ed['special_advice']))
                            <p style="font-size:10.5px;font-style:italic;color:#444;margin-top:3px;">
                                {{ $ed['special_advice'] }}
                            </p>
                        @endif

                        {{-- Medicines --}}
                        @if($exam->prescriptions->isNotEmpty())
                            <div class="rx-sub-title">Medicines (Rx)</div>
                            <table class="rx-medicines">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Medicine</th>
                                        <th>Dosage</th>
                                        <th>Duration</th>
                                        <th>Eye</th>
                                        <th>Instructions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($exam->prescriptions as $i => $rx)
                                        <tr>
                                            <td style="text-align:center">{{ $i + 1 }}</td>
                                            <td>
                                                <span style="font-weight:600">{{ $rx->medicine?->name ?? '—' }}</span>
                                                @if($rx->medicine?->brand_name)
                                                    <br><span class="med-brand">{{ $rx->medicine->brand_name }}</span>
                                                @endif
                                                @if($rx->medicine?->medicineType)
                                                    <span class="med-brand"> ({{ $rx->medicine->medicineType->name }})</span>
                                                @endif
                                            </td>
                                            <td>{{ $rx->dosage?->dosage ?? '—' }}</td>
                                            <td>{{ $rx->duration ? $rx->duration . 'd' : '—' }}</td>
                                            <td>{{ $rx->eye ?? '—' }}</td>
                                            <td>{{ $rx->instructions ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif

                        {{-- Follow-up --}}
                        @if(!empty($ed['followup_date']) || !empty($ed['followup_duration']))
                            <div style="font-size:10.5px;margin-top:4px;">
                                <strong style="color:#1B3A5C">Follow-up:</strong>
                                @if(!empty($ed['followup_date']))
                                    {{ \Carbon\Carbon::parse($ed['followup_date'])->format('d M Y') }}
                                @endif
                                @if(!empty($ed['followup_duration']))
                                    ({{ $ed['followup_duration'] }})
                                @endif
                            </div>
                        @endif

                    </div>
                </div>{{-- /Box 2 --}}

            </div>{{-- /LEFT COLUMN --}}

            {{-- ══ RIGHT COLUMN ══ --}}
            <div class="rx-col">

                {{-- Box 3: On Examination (O/E) --}}
                <div class="rx-box">
                    <div class="rx-box-title">O/E</div>
                    <div class="rx-box-body" style="padding:0">
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
                        <table class="oe-table">
                            <thead>
                                <tr>
                                    <th style="width:70px">O/E</th>
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
                </div>

                {{-- Box 4: Fundus --}}
                <div class="rx-box">
                    <div class="rx-box-title">Fundus</div>
                    <div class="rx-box-body" style="padding:0">
                        <table class="fundus-table">
                            <thead>
                                <tr>
                                    <th style="width:70px">Fundus</th>
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
                </div>

            </div>{{-- /RIGHT COLUMN --}}

        </div>{{-- /rx-grid --}}

        {{-- ── Signature ── --}}
        <div class="rx-footer">
            <div></div>
            <div class="rx-signature">
                <div class="sig-line"></div>
                <div>{{ $exam->doctor?->name ?? '' }}</div>
                <div style="font-size:9px;color:#666">Signature / Stamp</div>
            </div>
        </div>

    </div>{{-- /page --}}

</body>

</html>