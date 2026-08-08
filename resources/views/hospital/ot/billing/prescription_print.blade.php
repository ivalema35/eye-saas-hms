<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Post-Op Prescription</title>
    <style>
        @page {
            size: A5 portrait;
            margin: 8mm;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #fff;
            color: #111827;
            font-size: 12px;
        }
        /* Base styles must already fit inside @page's own margin — DomPDF
           (used for the app's PDF export) ignores @media print entirely, so
           a "card preview" width/padding here would overflow the printable
           canvas and get clipped. Real browsers reset to this exact layout
           via @media print anyway, so there's no visual loss when printing
           from web. See OT_DISCHARGE_INVOICES_WEB_PARITY_FIX_PLAN.md
           Addendum (PDF clipping bug). */
        .page {
            width: 100%;
            min-height: auto;
            margin: 0;
            background: #fff;
            padding: 0;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }
        .header { border-bottom: 2px solid #0d5c63; margin-bottom: 10px; padding-bottom: 6px; }
        .header h1 { margin: 0; color: #0d5c63; font-size: 16px; }
        .meta { font-size: 11px; line-height: 1.5; margin-bottom: 10px; }
        .meta strong { color: #374151; }
        .rx-symbol { font-size: 26px; font-weight: 700; color: #0d5c63; margin: 6px 0; }
        ul { margin: 6px 0 0 16px; padding: 0; font-size: 11px; }
        li { margin-bottom: 6px; }
        .signature { margin-top: auto; padding-top: 24px; text-align: right; font-size: 11px; }
        .signature .line { border-top: 1px solid #9ca3af; width: 160px; margin-left: auto; padding-top: 4px; }
        .print-btn { position: fixed; right: 12px; top: 12px; border: 0; background: #0d5c63; color: #fff; padding: 8px 12px; border-radius: 8px; cursor: pointer; }
        @media print {
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
            <h1>Post-Op Prescription</h1>
        </div>
    @else
        <div class="header" style="display:flex;align-items:center;gap:10px;">
            <div style="width:56px;height:56px;border-radius:12px;background:#F8FAFC;border:1px solid #E5E7EB;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                @if(hospital_logo_url())
                    <img src="{{ hospital_logo_url() }}" alt="{{ hospital_name() }} logo" style="width:100%;height:100%;object-fit:contain;padding:6px;">
                @else
                    <span style="font-size:22px;color:#0d5c63">👁</span>
                @endif
            </div>
            <div>
                <h1>{{ hospital_name() }} - Post-Op Prescription</h1>
                <div style="font-size:11px;color:#4b5563;">Tenant: {{ $slug }}</div>
            </div>
        </div>
    @endif

    <div class="meta">
        <div><strong>Patient:</strong> {{ $booking->patient?->full_name ?? '-' }} ({{ $booking->patient?->patient_code ?? '-' }})</div>
        <div><strong>Doctor:</strong> {{ $booking->otDoctor?->name ?? '-' }}</div>
        <div><strong>Surgery:</strong> {{ $surgery->surgery_name ?? $booking->ot_type ?? '-' }} — Eye: {{ $surgery->eye_operated ?? $booking->eye ?? '-' }}</div>
        <div><strong>Date:</strong> {{ optional($booking->operated_at)->format('d M Y') ?? optional($booking->surgery_date)->format('d M Y') }}</div>
    </div>

    <div class="rx-symbol">℞</div>

    @if(!empty($wardMedicines))
        <ul>
            @foreach($wardMedicines as $medicine)
                <li>
                    <strong>{{ $medicine['medicine'] ?? 'Medicine' }}</strong>
                    @if(!empty($medicine['dose']))
                        — {{ $medicine['dose'] }}
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <p style="font-size:11px;color:#6b7280;">No medicines prescribed.</p>
    @endif

    <div class="signature">
        <div class="line">Doctor's Signature</div>
    </div>
</div>
</body>
</html>
