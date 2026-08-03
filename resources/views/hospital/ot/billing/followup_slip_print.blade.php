<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Follow-up Appointment Slip</title>
    <style>
        @page {
            size: A5 portrait;
            margin: 8mm;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f4f6fb;
            color: #111827;
            font-size: 12px;
        }
        .page {
            width: 148mm;
            min-height: 210mm;
            margin: 12px auto;
            background: #fff;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            padding: 8mm;
            box-sizing: border-box;
        }
        .header { border-bottom: 2px solid #0f4c81; margin-bottom: 10px; padding-bottom: 6px; }
        .header h1 { margin: 0; color: #0f4c81; font-size: 16px; }
        .meta { font-size: 11px; line-height: 1.6; margin-bottom: 14px; }
        .meta strong { color: #374151; }
        .followup-box {
            border: 2px dashed #0f4c81;
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            margin-top: 10px;
        }
        .followup-box .label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; }
        .followup-box .date { font-size: 22px; font-weight: 700; color: #0f4c81; margin-top: 6px; }
        .note { margin-top: 20px; font-size: 11px; color: #4b5563; }
        .print-btn { position: fixed; right: 12px; top: 12px; border: 0; background: #0f4c81; color: #fff; padding: 8px 12px; border-radius: 8px; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .page { width: 100%; min-height: auto; margin: 0; box-shadow: none; padding: 0; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
<button class="print-btn" onclick="window.print()">Print</button>

<div class="page">
    @php $letterPad = hospital_setting('letter_pad', 'unavailable'); @endphp
    @if($letterPad === 'available')
        <div class="header">
            <h1>Follow-up Appointment Slip</h1>
        </div>
    @else
        <div class="header" style="display:flex;align-items:center;gap:10px;">
            <div style="width:56px;height:56px;border-radius:12px;background:#F8FAFC;border:1px solid #E5E7EB;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                @if(hospital_logo_url())
                    <img src="{{ hospital_logo_url() }}" alt="{{ hospital_name() }} logo" style="width:100%;height:100%;object-fit:contain;padding:6px;">
                @else
                    <span style="font-size:22px;color:#0f4c81">👁</span>
                @endif
            </div>
            <div>
                <h1>{{ hospital_name() }} - Follow-up Slip</h1>
                <div style="font-size:11px;color:#4b5563;">Tenant: {{ $slug }}</div>
            </div>
        </div>
    @endif

    <div class="meta">
        <div><strong>Patient:</strong> {{ $booking->patient?->full_name ?? '-' }} ({{ $booking->patient?->patient_code ?? '-' }})</div>
        <div><strong>Doctor:</strong> {{ $booking->otDoctor?->name ?? '-' }}</div>
        <div><strong>Surgery Date:</strong> {{ optional($booking->operated_at)->format('d M Y') ?? optional($booking->surgery_date)->format('d M Y') }}</div>
    </div>

    <div class="followup-box">
        <div class="label">Please Report Back On</div>
        <div class="date">{{ $invoice->follow_up_date ? \Illuminate\Support\Carbon::parse($invoice->follow_up_date)->format('d M Y') : 'To be advised' }}</div>
    </div>

    <div class="note">
        Please bring this slip and your discharge summary on the day of your follow-up visit.
    </div>
</div>
</body>
</html>
