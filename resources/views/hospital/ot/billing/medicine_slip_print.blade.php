<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Take-Home Medicine Slip</title>
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
        .meta { margin-top: 8px; font-size: 11px; line-height: 1.55; }
        .meta strong { color: #374151; }
        ul { margin-top: 8px; font-size: 11px; }
        li { margin-bottom: 6px; }
        .print-btn {
            position: fixed; right: 12px; top: 12px; border: 0;
            background: #0f4c81; color: #fff; padding: 8px 12px; border-radius: 8px; cursor: pointer;
        }
        @media print {
            .print-btn { display: none; }
            body { background: #fff; }
            .page { margin: 0; box-shadow: none; width: auto; min-height: auto; }
        }
    </style>
</head>
<body>
<button class="print-btn" onclick="window.print()">Print</button>
@php $letterPad = hospital_setting('letter_pad', 'unavailable'); @endphp
<div class="page">
@if($letterPad === 'available')
<div class="header">
    <h1>Take-Home Medicine Slip</h1>
    <div class="meta">Patient: {{ $booking->patient?->full_name ?? '-' }} | Doctor: {{ $booking->otDoctor?->name ?? '-' }}</div>
    <div class="meta">Date: {{ optional($booking->discharged_at)->format('d M Y h:i A') ?? now()->format('d M Y h:i A') }}</div>
</div>
@else
<div class="header" style="display:flex;align-items:center;gap:10px;">
    <div style="width:56px;height:56px;border-radius:10px;background:#F8FAFC;border:1px solid #E5E7EB;display:flex;align-items:center;justify-content:center;overflow:hidden;">
        @if(hospital_logo_url())
            <img src="{{ hospital_logo_url() }}" alt="{{ hospital_name() }} logo" style="width:100%;height:100%;object-fit:contain;padding:6px;">
        @else
            <span style="font-size:22px;color:#0f4c81">👁</span>
        @endif
    </div>
    <div>
        <h1>Take-Home Medicine Slip</h1>
        <div class="meta">{{ hospital_name() }}</div>
        <div class="meta">Patient: {{ $booking->patient?->full_name ?? '-' }} | Doctor: {{ $booking->otDoctor?->name ?? '-' }}</div>
        <div class="meta">Date: {{ optional($booking->discharged_at)->format('d M Y h:i A') ?? now()->format('d M Y h:i A') }}</div>
    </div>
</div>
@endif

@if(!empty($wardMedicines))
    <ul>
        @foreach($wardMedicines as $medicine)
            <li><strong>{{ $medicine['medicine'] ?? 'Medicine' }}</strong> @if(!empty($medicine['dose'])) - {{ $medicine['dose'] }} @endif</li>
        @endforeach
    </ul>
@else
    <p style="font-size:11px;color:#6b7280;">No take-home medicines recorded.</p>
@endif
</div>
</body>
</html>
