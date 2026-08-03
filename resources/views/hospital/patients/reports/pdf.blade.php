<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1A202C;
        }
        .header {
            border-bottom: 2px solid #1B4F72;
            margin-bottom: 12px;
            padding-bottom: 8px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            color: #1B4F72;
            margin: 0;
        }
        .meta {
            margin-top: 4px;
            color: #4A5568;
            font-size: 11px;
        }
        .summary {
            background: #D5F5E3;
            border: 1px solid #27AE60;
            padding: 8px 10px;
            margin-bottom: 12px;
            font-weight: bold;
            color: #1A6F5B;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead th {
            background: #1B4F72;
            color: #fff;
            text-align: left;
            padding: 7px;
            font-size: 11px;
        }
        tbody td {
            border: 1px solid #E2E8F0;
            padding: 6px;
            font-size: 11px;
        }
        tbody tr:nth-child(even) {
            background: #F8FAFC;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .muted {
            color: #4A5568;
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">Patient Reports</p>
        <div class="meta">
            Generated: {{ now()->format('d M Y, h:i A') }}
            @if(!empty($filters['date_range']))
                | Date Range: {{ $filters['date_range'] }}
            @endif
        </div>
    </div>

    <div class="summary">
        Total Collection (Walk-in): {{ money((float) $totalCollection, 2) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>MRD</th>
                <th>Patient Name</th>
                <th>Contact</th>
                <th>City</th>
                <th>Age</th>
                <th>Doctor</th>
                <th>Receptionist</th>
                <th>Case Type</th>
                <th>Type</th>
                <th class="text-right">Fee ({{ currency_symbol() }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse($patients as $patient)
                @php
                    $patientTypeLabel = in_array((string) $patient->type, ['walkin', '0'], true) ? 'Walk-in' : 'Phone';
                    $caseTypeValue = strtolower(trim((string) ($patient->caseType?->case_type ?? '')));
                    $caseTypeLabel = match (true) {
                        str_contains($caseTypeValue, 'general') => 'General',
                        str_contains($caseTypeValue, 'old') => 'Old',
                        str_contains($caseTypeValue, 'new') => 'New',
                        default => $patient->caseType?->case_type ?: '-',
                    };
                @endphp
                <tr>
                    <td>{{ $patient->created_at?->format('d-m-Y h:i A') }}</td>
                    <td>{{ $patient->patient_code ?: '-' }}</td>
                    <td>{{ $patient->full_name }}</td>
                    <td>{{ $patient->contact_no ?: '-' }}</td>
                    <td>{{ $patient->cityName ?: '-' }}</td>
                    <td>{{ $patient->age ?: '-' }}</td>
                    <td>{{ $patient->doctor?->name ?: '-' }}</td>
                    <td>{{ $patient->reception?->name ?: '-' }}</td>
                    <td>{{ $caseTypeLabel }}</td>
                    <td>{{ $patientTypeLabel }}</td>
                    <td class="text-right">{{ number_format((float) $patient->case_fee, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center muted">No records found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
