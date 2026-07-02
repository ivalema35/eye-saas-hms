<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient History Print — {{ $patient->patient_code }}</title>
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f4f6f9;
            color: #1B4F72;
        }

        .print-shell {
            max-width: 1100px;
            margin: 24px auto;
            background: #fff;
            border: 1px solid #d9e6f2;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 18px 44px rgba(27, 79, 114, .10);
        }

        .print-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            padding: 22px 24px;
            border-bottom: 1px solid #e6eef6;
            background: linear-gradient(135deg, rgba(235, 245, 251, .95), rgba(255, 255, 255, .98));
        }

        .brand {
            display: flex;
            align-items: center;
            gap: .85rem;
        }

        .brand-mark {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: #1B4F72;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .brand h1 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 900;
            color: #0D2137;
        }

        .brand p,
        .patient-summary p {
            margin: 0;
            color: #5f7489;
            font-size: .92rem;
        }

        .print-actions {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .print-btn {
            border: 1px solid #d6e8ff;
            background: #eaf3ff;
            color: #4e79b8;
            border-radius: 12px;
            padding: .65rem 1rem;
            font-weight: 800;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
        }

        .print-btn:hover {
            background: #ddebff;
            color: #4e79b8;
        }

        .print-body {
            padding: 24px;
        }

        .patient-summary {
            display: grid;
            grid-template-columns: 1.15fr .85fr;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .summary-card,
        .timeline-card {
            border: 1px solid #d9e6f2;
            border-radius: 16px;
            overflow: hidden;
        }

        .summary-card .card-head,
        .timeline-card .card-head {
            padding: .9rem 1rem;
            background: #1B4F72;
            color: #fff;
            font-weight: 800;
        }

        .summary-card .card-body,
        .timeline-card .card-body {
            padding: 1rem;
        }

        .patient-code {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .4rem .75rem;
            border-radius: 999px;
            background: #ebf5fb;
            border: 1px solid #d6e8ff;
            font-weight: 900;
            margin-bottom: .85rem;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .85rem;
        }

        .summary-item {
            background: #f8fbfe;
            border: 1px solid #e6eef6;
            border-radius: 12px;
            padding: .75rem;
        }

        .summary-item label {
            display: block;
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .08em;
            color: #6b7f93;
            margin-bottom: .25rem;
            text-transform: uppercase;
        }

        .summary-item span {
            font-weight: 800;
            color: #0d2137;
        }

        .timeline-item {
            border: 1px solid #e6eef6;
            border-left: 5px solid #1B4F72;
            border-radius: 14px;
            padding: 1rem;
            margin-bottom: 1rem;
            page-break-inside: avoid;
        }

        .timeline-top {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: flex-start;
            margin-bottom: .65rem;
            flex-wrap: wrap;
        }

        .timeline-top h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 900;
            color: #1B4F72;
        }

        .exam-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            border-radius: 999px;
            padding: .35rem .7rem;
            font-size: .78rem;
            font-weight: 800;
            background: #ebf5fb;
            color: #1B4F72;
            border: 1px solid #d6e8ff;
        }

        .exam-meta {
            font-size: .9rem;
            color: #5f7489;
            margin-bottom: .65rem;
        }

        .exam-section {
            margin-top: 1rem;
        }

        .exam-section-title {
            padding: 0.75rem 1rem;
            background: #1B4F72;
            color: #fff;
            font-weight: 800;
            font-size: 0.9rem;
            border-radius: 8px 8px 0 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .exam-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #d9e6f2;
            border-top: none;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .exam-table th {
            background: #ebf5fb;
            color: #1B4F72;
            font-weight: 800;
            padding: 0.65rem;
            text-align: left;
            border-bottom: 2px solid #d9e6f2;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .exam-table td {
            padding: 0.65rem;
            border-bottom: 1px solid #e6eef6;
            color: #0d2137;
            font-weight: 600;
        }

        .exam-table tr:last-child td {
            border-bottom: none;
        }

        .exam-table tr:nth-child(even) {
            background: #f8fbfe;
        }

        .vision-metric {
            display: inline-block;
            background: #ebf5fb;
            padding: 0.4rem 0.7rem;
            border-radius: 6px;
            margin-right: 0.5rem;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .vision-metric-label {
            font-weight: 900;
            color: #1B4F72;
            font-size: 0.7rem;
        }

        .vision-metric-value {
            display: block;
            font-weight: 800;
            color: #0d2137;
            margin-top: 0.2rem;
        }

        .advice-box {
            background: #f8fbfe;
            border-left: 4px solid #1B4F72;
            padding: 1rem;
            border-radius: 8px;
            line-height: 1.6;
            color: #0d2137;
        }

        .advice-box p {
            margin: 0.35rem 0;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .7rem;
            margin-top: .75rem;
        }

        .detail-item {
            background: #f8fbfe;
            border: 1px solid #e6eef6;
            border-radius: 12px;
            padding: .65rem .75rem;
        }

        .detail-item .label {
            display: block;
            font-size: .7rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #6b7f93;
            margin-bottom: .2rem;
        }

        .detail-item .value {
            font-weight: 800;
            color: #0d2137;
            word-break: break-word;
        }

        .empty-state {
            padding: 2rem 1rem;
            text-align: center;
            color: #6b7f93;
            background: #f8fbfe;
            border: 1px dashed #cfe1f5;
            border-radius: 14px;
        }

        @media print {
            body {
                background: #fff;
            }

            .print-shell {
                margin: 0;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .print-actions {
                display: none !important;
            }

            .timeline-item {
                break-inside: avoid;
            }
        }

        @media (max-width: 992px) {

            .patient-summary,
            .detail-grid {
                grid-template-columns: 1fr;
            }

            .print-header {
                flex-direction: column;
            }

            .print-actions {
                justify-content: flex-start;
            }
        }
    </style>
</head>

<body>
    <div class="print-shell">
        <div class="print-header">
            @php $letterPad = hospital_setting('letter_pad', 'unavailable'); @endphp
            <div class="brand">
                @if($letterPad !== 'available')
                    <div class="brand-mark"><i class="bi bi-clock-history"></i></div>
                @endif
                <div>
                    <h1>Patient History</h1>
                    @if($letterPad !== 'available')
                        <p>{{ hospital_name() }} | {{ hospital_contact_number() ?: '—' }}</p>
                    @endif
                </div>
            </div>
            <div class="print-actions">
                <button type="button" class="print-btn" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
                <a href="{{ route('hospital.patients.history', [
                    'slug' => $slug,
                    'patient_ids' => $patient->id,
                ]) }}" class="print-btn">
                     Back
                </a>
            </div>
        </div>

        <div class="print-body">
            <div class="patient-summary">
                <div class="summary-card">
                    <div class="card-head">Patient Details</div>
                    <div class="card-body">
                        <div class="patient-code">
                            <i class="bi bi-file-earmark-medical"></i>
                            MRD: {{ $patient->patient_code }}
                        </div>
                        <h2 style="margin:0 0 .35rem;font-size:1.35rem;font-weight:900;color:#0d2137;">
                            {{ $patient->first_name }}
                            @if($patient->middle_name) {{ $patient->middle_name }} @endif
                            {{ $patient->last_name }}
                        </h2>
                        <p>{{ ucfirst($patient->gender) }}, {{ $patient->age }} years</p>

                        <div class="summary-grid mt-3">
                            <div class="summary-item">
                                <label>Contact</label>
                                <span>{{ $patient->contact_no }}</span>
                            </div>
                            <div class="summary-item">
                                <label>Location</label>
                                <span>{{ $patient->locationLabel }}</span>
                            </div>
                            <div class="summary-item">
                                <label>Registered</label>
                                <span>{{ $patient->created_at->format('d M Y') }}</span>
                            </div>
                            <div class="summary-item">
                                <label>Visits</label>
                                <span>{{ $history->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="card-head">Quick Info</div>
                    <div class="card-body">
                        <div class="summary-grid">
                            <div class="summary-item">
                                <label>MRD No.</label>
                                <span>{{ $patient->patient_code }}</span>
                            </div>
                            <div class="summary-item">
                                <label>Age / Gender</label>
                                <span>{{ $patient->age }} / {{ ucfirst($patient->gender) }}</span>
                            </div>
                            <div class="summary-item">
                                <label>Contact No.</label>
                                <span>{{ $patient->contact_no }}</span>
                            </div>
                            <div class="summary-item">
                                <label>Last Updated</label>
                                <span>{{ $patient->updated_at->format('d M Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="timeline-card">
                <div class="card-head">Clinical Timeline</div>
                <div class="card-body">
                    @if($history->isEmpty())
                        <div class="empty-state">
                            No examination history found for this patient.
                        </div>
                    @else
                        @foreach($history as $exam)
                            @php
        $data = is_array($exam->exam_data)
            ? $exam->exam_data
            : (json_decode($exam->exam_data, true) ?? []);
                            @endphp
                            <div class="timeline-item">
                                <div class="timeline-top">
                                    <div>
                                        <h3>
                                            <i class="bi {{ $exam->icon }} me-1"></i>
                                            {{ $exam->type }}
                                        </h3>
                                        <div class="exam-meta">
                                            Examined by <strong>Dr. {{ $exam->doctor->name ?? 'Unknown' }}</strong>
                                        </div>
                                    </div>
                                    <span class="exam-badge">
                                        <i class="bi bi-calendar-event"></i>
                                        {{ \Carbon\Carbon::parse($exam->examined_at)->format('d M Y, h:i A') }}
                                    </span>
                                </div>

                                @if(!empty($data))
                                    @php
                                        $cv = fn($v) => (isset($v) && $v !== '' && $v !== null) ? $v : '-';
                                        $coRows = array_filter($data['co_rows'] ?? [], fn($r) => !empty($r['complaint']));
                                        $kcoRows = array_filter($data['kco_rows'] ?? [], fn($r) => !empty($r['condition']));
                                        $vision = $data['vision'] ?? [];
                                        $pg = $data['pg'] ?? [];
                                        $st = $data['st'] ?? [];
                                        $nct = $data['nct'] ?? [];
                                        $oe = $data['oe'] ?? [];
                                        $fundus = $data['fundus'] ?? [];
                                        $advTxt = trim($data['advice'] ?? '');
                                    @endphp

                                    {{-- HISTORY & VISION --}}
                                    <div class="exam-section">
                                        <div class="exam-section-title">History &amp; Vision</div>
                                        <table class="exam-table">
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
                                                        <td>{{ $cr['complaint'] }}</td>
                                                        <td>{{ !empty($cr['since']) ? $cr['since'] . ' ' . ($cr['unit'] ?? '') : '-' }}</td>
                                                        <td>{{ $cr['eye'] ?? '-' }}</td>
                                                        <td>{{ $cr['comment'] ?? '-' }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" style="text-align: center;">—</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>

                                        @if(count($kcoRows))
                                            <table class="exam-table">
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
                                                            <td>{{ $kr['condition'] }}</td>
                                                            <td>{{ !empty($kr['since']) ? $kr['since'] . ' ' . ($kr['unit'] ?? '') : '-' }}</td>
                                                            <td>{{ $kr['comment'] ?? '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif

                                        <div style="border-top:1px solid #d9e6f2; padding-top:0.75rem; margin-top:0.75rem; display:flex; flex-wrap:wrap; gap:0.5rem; font-size:0.85rem;">
                                            <span><strong>Vn</strong>&nbsp;{{ $cv($vision['vn_re'] ?? '') }}/{{ $cv($vision['vn_le'] ?? '') }}</span>
                                            <span><strong>PH</strong>&nbsp;{{ $cv($vision['pnvn_re'] ?? '') }}/{{ $cv($vision['pnvn_le'] ?? '') }}</span>
                                            <span><strong>NrVn</strong>&nbsp;{{ $cv($vision['nrvn_re'] ?? '') }}/{{ $cv($vision['nrvn_le'] ?? '') }}</span>
                                            <span><strong>IOP:</strong>&nbsp;{{ $cv($nct['iop_re'] ?? '') }}/{{ $cv($nct['iop_le'] ?? '') }}</span>
                                        </div>

                                        {{-- PG Table --}}
                                        <table class="exam-table" style="margin-top: 0.75rem;">
                                            <thead>
                                                <tr>
                                                    <th style="width:20px"></th>
                                                    <th colspan="4" style="text-align: center;">RIGHT EYE (RE)</th>
                                                    <th colspan="4" style="text-align: center;">LEFT EYE (LE)</th>
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

                                    {{-- O/E SECTION --}}
                                    @if(!empty($oe))
                                        <div class="exam-section">
                                            <div class="exam-section-title">O/E</div>
                                            <table class="exam-table">
                                                <thead>
                                                    <tr>
                                                        <th>O/E</th>
                                                        <th>RIGHT</th>
                                                        <th>LEFT</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($oe as $param => $values)
                                                        <tr>
                                                            <td>{{ \Illuminate\Support\Str::headline($param) }}</td>
                                                            <td>{{ $values['right'] ?? '-' }}</td>
                                                            <td>{{ $values['left'] ?? '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif

                                    {{-- ST (SPECTACLES TEST) --}}
                                    <div class="exam-section">
                                        <div class="exam-section-title">ST</div>
                                        <table class="exam-table">
                                            <thead>
                                                <tr>
                                                    <th style="width:20px"></th>
                                                    <th colspan="4" style="text-align: center;">RIGHT EYE (RE)</th>
                                                    <th colspan="4" style="text-align: center;">LEFT EYE (LE)</th>
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
                                            <div style="font-size:0.85rem; color:#1B4F72; font-weight:700; margin-top:0.5rem;">
                                                ADD &emsp; RE: <strong>{{ $st['re']['add'] ?? '-' }}</strong> &emsp; LE: <strong>{{ $st['le']['add'] ?? '-' }}</strong>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- FUNDUS SECTION --}}
                                    @if(!empty($fundus))
                                        <div class="exam-section">
                                            <div class="exam-section-title">Fundus</div>
                                            <table class="exam-table">
                                                <thead>
                                                    <tr>
                                                        <th>Fundus</th>
                                                        <th>RIGHT</th>
                                                        <th>LEFT</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($fundus as $param => $values)
                                                        <tr>
                                                            <td>{{ \Illuminate\Support\Str::headline($param) }}</td>
                                                            <td>{{ $values['right'] ?? '-' }}</td>
                                                            <td>{{ $values['left'] ?? '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif

                                    {{-- DIAGNOSIS & RX --}}
                                    <div class="exam-section">
                                        <div class="exam-section-title">Diagnosis &amp; Rx</div>
                                        <div style="padding: 0.75rem; background: #f8fbfe; border: 1px solid #d9e6f2; border-top: none; border-radius: 0 0 8px 8px; margin-bottom: 1rem;">
                                            <div style="font-size: 0.85rem; margin-bottom: 0.5rem;">
                                                <strong>Dx:</strong> {{ isset($data['diagnoses']) && $data['diagnoses'] ? implode(', ', (array)$data['diagnoses']) : '-' }}&nbsp;&nbsp;
                                                <strong>Dilate:</strong> {{ $data['dilate'] ?? 'No' }}
                                            </div>
                                        </div>
                                    </div>

                                    {{-- MEDICINES/RX TABLE --}}
                                    @php
                                        $rxLines = collect($data['rx'] ?? []);
                                    @endphp
                                    @if($rxLines->isNotEmpty())
                                        <div class="exam-section">
                                            <div class="exam-section-title">Medicines</div>
                                            <table class="exam-table">
                                                <thead>
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
                                                            $rx = (array) $rx;
                                                            $mName = $rx['name'] ?? '-';
                                                            $mDose = $rx['dosage'] ?? '-';
                                                            $mDays = !empty($rx['duration']) ? $rx['duration'] . ' D' : '-';
                                                            $mEye = $rx['eye'] ?? '-';
                                                        @endphp
                                                        <tr>
                                                            <td>{{ $mName }}</td>
                                                            <td>{{ $mDose }}</td>
                                                            <td>{{ $mDays }}</td>
                                                            <td>{{ $mEye }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="4" style="text-align: center; color: #6b7f93;">No medicines prescribed</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif

                                    {{-- ADVICE SECTION --}}
                                    @if(!empty($advTxt))
                                        <div class="exam-section">
                                            <div class="exam-section-title">Advice</div>
                                            <div class="advice-box">
                                                {{ $advTxt }}
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    <div class="empty-state mt-3 mb-0">No structured data recorded.</div>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>

</html>