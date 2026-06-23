<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OT Discharge Summary</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f4f6fb;
            color: #111827;
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
            border-bottom: 2px solid #0d5c63;
            margin-bottom: 16px;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #0d5c63;
            font-size: 24px;
        }
        .header p {
            margin: 4px 0 0;
            color: #4b5563;
            font-size: 13px;
        }
        .section {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
        }
        .section h3 {
            margin: 0 0 8px;
            font-size: 14px;
            color: #0f172a;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px 14px;
            font-size: 13px;
        }
        .label {
            color: #6b7280;
        }
        ul {
            margin: 8px 0 0 18px;
            padding: 0;
            font-size: 13px;
        }
        li {
            margin-bottom: 6px;
        }
        .print-btn {
            position: fixed;
            top: 14px;
            right: 14px;
            border: 0;
            background: #0d5c63;
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
            <h1>OT Discharge Summary</h1>
            <p>Prepared: {{ now()->format('d M Y h:i A') }}</p>
        </div>
        @else
        <div class="header">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="print-logo" style="width:72px;height:72px;border-radius:12px;background:#F8FAFC;border:1px solid #E5E7EB;display:flex;align-items:center;justify-content:center;overflow:hidden;margin-right:12px;">
                    @if(hospital_logo_url())
                        <img src="{{ hospital_logo_url() }}" alt="{{ hospital_name() }} logo" style="width:100%;height:100%;object-fit:contain;padding:8px;">
                    @else
                        <span>👁</span>
                    @endif
                </div>
                <div>
                    <h1>{{ hospital_name() }} - OT Discharge Summary</h1>
                    <p>Tenant: {{ $slug }} | Prepared: {{ now()->format('d M Y h:i A') }}</p>
                </div>
            </div>
        </div>
        @endif

        <div class="section">
            <h3>Patient & Surgery Information</h3>
            <div class="grid">
                <div><span class="label">Patient Name:</span> {{ $booking->patient?->full_name ?? '-' }}</div>
                <div><span class="label">Patient Code:</span> {{ $booking->patient?->patient_code ?? '-' }}</div>
                <div><span class="label">Phone:</span> {{ $booking->patient?->contact_no ?? '-' }}</div>
                <div><span class="label">Doctor:</span> {{ $booking->otDoctor?->name ?? '-' }}</div>
                <div><span class="label">Date of Surgery:</span> {{ optional($booking->operated_at)->format('d M Y h:i A') ?? optional($booking->surgery_date)->format('d M Y') }}</div>
                <div><span class="label">Eye Operated:</span> {{ optional($surgery)->eye_operated ?? $booking->eye ?? '-' }}</div>
            </div>
        </div>

        <div class="section">
            <h3>Clinical Notes</h3>
            <div class="grid">
                <div><span class="label">Complication Status:</span> {{ strtoupper((string) (optional($surgery)->complication_status ?? 'none')) }}</div>
                <div><span class="label">Surgery Status:</span> {{ strtoupper((string) (optional($surgery)->surgery_status ?? $booking->ot_status)) }}</div>
            </div>
            <div style="margin-top:8px;font-size:13px;">
                <span class="label">Complication Notes:</span>
                <div>{{ optional($surgery)->complication_notes ?? optional($surgery)->complication ?? 'None' }}</div>
            </div>
        </div>

        <div class="section">
            <h3>Take-Home Medicines</h3>
            @if(!empty($wardMedicines))
                <ul>
                    @foreach($wardMedicines as $medicine)
                        <li>
                            <strong>{{ $medicine['medicine'] ?? 'Medicine' }}</strong>
                            @if(!empty($medicine['dose']))
                                - {{ $medicine['dose'] }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p style="margin:0;font-size:13px;color:#6b7280;">No ward medicines recorded.</p>
            @endif
        </div>

        <div style="margin-top:28px;font-size:12px;color:#6b7280;">
            This discharge summary is generated digitally for OT completion records.
        </div>
    </div>
</body>
</html>
