<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OPD Bill — {{ $patient->patient_code }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; font-size: 12px; }

        .bill-container { padding: 24px; }

        .header { text-align: center; border-bottom: 2px solid #1B4F72; padding-bottom: 12px; margin-bottom: 16px; }
        .header h1 { font-size: 18px; font-weight: 800; color: #0D2137; margin-bottom: 2px; }
        .header p { font-size: 10px; color: #64748B; }

        .bill-title { text-align: center; font-size: 14px; font-weight: 700; color: #1B4F72; margin-bottom: 14px; text-transform: uppercase; letter-spacing: 1px; }

        .info-table { width: 100%; margin-bottom: 16px; }
        .info-table td { padding: 3px 8px; vertical-align: top; }
        .info-label { font-weight: 600; color: #64748B; font-size: 10px; text-transform: uppercase; }
        .info-value { color: #111827; font-size: 11px; }

        .bill-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .bill-table th { background: #F1F5F9; color: #374151; font-size: 10px; text-transform: uppercase; padding: 6px 10px; text-align: left; border-bottom: 2px solid #D1D5DB; }
        .bill-table td { padding: 8px 10px; border-bottom: 1px solid #E5E7EB; font-size: 11px; }
        .bill-table .total-row td { font-weight: 700; font-size: 12px; border-top: 2px solid #1B4F72; background: #F8FAFC; }

        .footer { margin-top: 40px; }
        .footer-left { float: left; font-size: 9px; color: #9CA3AF; }
        .footer-right { float: right; text-align: right; }
        .signature-line { width: 140px; border-top: 1px solid #111827; margin-bottom: 4px; }
        .signature-label { font-size: 10px; color: #64748B; }
    </style>
</head>
<body>
<div class="bill-container">
    {{-- Header --}}
    <div class="header">
        <h1>{{ hospital_name() }}</h1>
        @if(hospital_full_address())
            <p>{{ hospital_full_address() }}</p>
        @endif
        <p>Phone: {{ hospital_contact_number() ?: '—' }} &bull; Email: {{ hospital_official_email() ?: '—' }}</p>
    </div>

    <div class="bill-title">OPD Bill / Receipt</div>

    {{-- Patient Info --}}
    <table class="info-table">
        <tr>
            <td><span class="info-label">MRD No.</span><br><span class="info-value"><strong>{{ $patient->patient_code }}</strong></span></td>
            <td><span class="info-label">Patient Name</span><br><span class="info-value">{{ $patient->full_name }}</span></td>
            <td><span class="info-label">Age / Gender</span><br><span class="info-value">{{ $patient->age }} yrs / {{ ucfirst($patient->gender) }}</span></td>
        </tr>
        <tr>
            <td><span class="info-label">Contact</span><br><span class="info-value">{{ $patient->contact_no }}</span></td>
            <td><span class="info-label">Consultant</span><br><span class="info-value">{{ $patient->doctor?->name ?? '—' }}</span></td>
            <td><span class="info-label">Date</span><br><span class="info-value">{{ $patient->appointment_date?->format('d M Y') }}</span></td>
        </tr>
        <tr>
            <td><span class="info-label">Receptionist</span><br><span class="info-value">{{ $patient->reception?->name ?? '—' }}</span></td>
            <td><span class="info-label">Type</span><br><span class="info-value">@if(str_contains(strtolower($patient->type ?? ''), 'phone')) Phone Call @else {{ ucfirst($patient->type) }} @endif</span></td>
            <td></td>
        </tr>
    </table>

    {{-- Billing Table --}}
    <table class="bill-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Description</th>
                <th style="text-align:right">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>OPD Consultation Fee</td>
                <td style="text-align:right">{{ number_format($patient->case_fee, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="2" style="text-align:right">Total Payable</td>
                <td style="text-align:right">₹{{ number_format($patient->case_fee, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Footer --}}
    <div class="footer">
        <div class="footer-left">
            <p>Generated: {{ now()->format('d M Y, h:i A') }}</p>
            <p>{{ hospital_name() }}</p>
        </div>
        <div class="footer-right">
            <div class="signature-line"></div>
            <span class="signature-label">Authorized Signature</span>
        </div>
        <div style="clear:both"></div>
    </div>
</div>
</body>
</html>
