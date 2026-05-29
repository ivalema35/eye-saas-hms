<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient History Print — {{ $patient->patient_code }}</title>
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { box-sizing: border-box; }
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
            background: linear-gradient(135deg, rgba(235,245,251,.95), rgba(255,255,255,.98));
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
            body { background: #fff; }
            .print-shell { margin: 0; border: 0; border-radius: 0; box-shadow: none; }
            .print-actions { display: none !important; }
            .timeline-item { break-inside: avoid; }
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
            <div class="brand">
                <div class="brand-mark"><i class="bi bi-clock-history"></i></div>
                <div>
                    <h1>Patient History</h1>
                    <p>{{ hospital_name() }} | {{ hospital_contact_number() ?: '—' }}</p>
                </div>
            </div>
            <div class="print-actions">
                <button type="button" class="print-btn" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
                <a href="{{ route('hospital.patients.history', ['slug' => $slug, 'search' => $patient->patient_code]) }}" class="print-btn">
                    <i class="bi bi-arrow-left"></i> Back
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
                                <span>{{ $patient->location?->name ?? 'N/A' }}</span>
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

                                @if(! empty($data))
                                    <div class="detail-grid">
                                        @foreach($data as $key => $value)
                                            @if(! is_array($value) && $value !== null && $value !== '')
                                                <div class="detail-item">
                                                    <span class="label">{{ \Illuminate\Support\Str::headline($key) }}</span>
                                                    <span class="value">{{ $value }}</span>
                                                </div>
                                            @elseif(is_array($value) && ! empty($value))
                                                <div class="detail-item" style="grid-column: 1 / -1;">
                                                    <span class="label">{{ \Illuminate\Support\Str::headline($key) }}</span>
                                                    <span class="value">{{ json_encode($value) }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
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
