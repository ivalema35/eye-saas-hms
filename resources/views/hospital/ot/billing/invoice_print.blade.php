<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OT Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f5f6fa;
            color: #1f2937;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 12px auto;
            background: #fff;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            padding: 20mm;
            box-sizing: border-box;
        }
        .header {
            border-bottom: 2px solid #0f4c81;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .print-logo {
            width: 72px;
            height: 72px;
            border-radius: 12px;
            background: #F8FAFC;
            border: 1px solid #E5E7EB;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-right: 12px;
        }
        .print-logo img { width: 100%; height: 100%; object-fit: contain; padding: 8px; }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #0f4c81;
        }
        .header p {
            margin: 4px 0 0;
            color: #4b5563;
            font-size: 13px;
        }
        .meta {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }
        .meta-box {
            flex: 1;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
            font-size: 13px;
            line-height: 1.55;
        }
        .meta-title {
            font-weight: 700;
            color: #111827;
            margin-bottom: 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 13px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 9px;
            text-align: left;
        }
        th {
            background: #f3f4f6;
            font-weight: 700;
        }
        .totals {
            width: 320px;
            margin-left: auto;
            margin-top: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
            font-size: 13px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        .totals-row.net {
            border-top: 1px solid #d1d5db;
            margin-top: 6px;
            padding-top: 10px;
            font-size: 14px;
            font-weight: 700;
        }
        .print-btn {
            position: fixed;
            top: 14px;
            right: 14px;
            border: 0;
            background: #0f4c81;
            color: #fff;
            padding: 10px 14px;
            border-radius: 8px;
            cursor: pointer;
        }
        @media print {
            body {
                background: #fff;
            }
            .page {
                width: 100%;
                min-height: auto;
                margin: 0;
                box-shadow: none;
                padding: 0;
            }
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">Print</button>

    <div class="page">
        @php $letterPad = hospital_setting('letter_pad', 'unavailable'); @endphp
        @if($letterPad === 'available')
        <div class="header" style="display:flex;justify-content:space-between;align-items:center;">
            <h1>OT Invoice</h1>
            <p style="margin:0;color:#4b5563;font-size:13px;">Generated: {{ now()->format('d M Y h:i A') }}</p>
        </div>
        @else
        <div class="header">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="print-logo">
                    @if(hospital_logo_url())
                        <img src="{{ hospital_logo_url() }}" alt="{{ hospital_name() }} logo">
                    @else
                        <span>👁</span>
                    @endif
                </div>
                <div>
                    <h1 style="margin:0">{{ hospital_name() }} - OT Invoice</h1>
                    <p style="margin:4px 0 0;color:#4b5563;font-size:13px;">Tenant: {{ $slug }} | Generated: {{ now()->format('d M Y h:i A') }}</p>
                </div>
            </div>
        </div>
        @endif

        <div class="meta">
            <div class="meta-box">
                <div class="meta-title">Patient Details</div>
                <div>Name: {{ $booking->patient?->full_name ?? '-' }}</div>
                <div>Patient Code: {{ $booking->patient?->patient_code ?? '-' }}</div>
                <div>Phone: {{ $booking->patient?->contact_no ?? '-' }}</div>
                <div>OT Date: {{ optional($booking->surgery_date)->format('d M Y') }}</div>
            </div>
            <div class="meta-box">
                <div class="meta-title">Invoice Details</div>
                <div>Invoice No: {{ $invoice->invoice_number ?? '-' }}</div>
                <div>Booking ID: {{ $booking->id }}</div>
                <div>Doctor: {{ $booking->otDoctor?->name ?? '-' }}</div>
                <div>Status: {{ strtoupper((string) $booking->ot_status) }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 70%">Particulars</th>
                    <th style="width: 30%">Amount (INR)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lineItems as $item)
                    <tr>
                        <td>
                            {{ $item['head'] ?? 'Charge' }}
                            @if(!empty($item['description']))
                                <div style="font-size:12px;color:#6b7280;">{{ $item['description'] }}</div>
                            @endif
                        </td>
                        <td>{{ number_format((float) ($item['amount'] ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td>Surgery Package</td>
                        <td>{{ number_format((float) ($invoice->total_amount ?? 0), 2) }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row">
                <span>Total</span>
                <strong>INR {{ number_format((float) ($invoice->total_amount ?? 0), 2) }}</strong>
            </div>
            <div class="totals-row">
                <span>Tax</span>
                <strong>INR {{ number_format((float) ($invoice->tax_amount ?? 0), 2) }}</strong>
            </div>
            <div class="totals-row">
                <span>Discount</span>
                <strong>INR {{ number_format((float) ($invoice->discount ?? 0), 2) }}</strong>
            </div>
            <div class="totals-row net">
                <span>Net Amount</span>
                <strong>INR {{ number_format((float) ($invoice->net_amount ?? 0), 2) }}</strong>
            </div>
        </div>
    </div>
</body>
</html>
