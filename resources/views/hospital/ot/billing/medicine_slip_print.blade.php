<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Take-Home Medicine Slip</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #111827; }
        h1 { margin: 0 0 12px; color: #0f4c81; }
        .meta { margin-top: 10px; font-size: 14px; line-height: 1.55; }
        .meta strong { color: #374151; }
        ul { margin-top: 8px; }
        li { margin-bottom: 6px; }
        .print-btn { position: fixed; right: 12px; top: 12px; }
        @media print { .print-btn { display:none; } body { margin: 0; } }
    </style>
</head>
<body>
<button class="print-btn" onclick="window.print()">Print</button>
@php $letterPad = hospital_setting('letter_pad', 'unavailable'); @endphp
@if($letterPad === 'available')
<div style="margin-bottom:8px;border-bottom:2px solid #0f4c81;padding-bottom:8px;">
    <h1 style="margin:0 0 6px">Take-Home Medicine Slip</h1>
    <div>Patient: {{ $booking->patient?->full_name ?? '-' }} | Doctor: {{ $booking->otDoctor?->name ?? '-' }}</div>
    <div>Date: {{ optional($booking->discharged_at)->format('d M Y h:i A') ?? now()->format('d M Y h:i A') }}</div>
</div>
@else
<div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
    <div style="width:72px;height:72px;border-radius:12px;background:#F8FAFC;border:1px solid #E5E7EB;display:flex;align-items:center;justify-content:center;overflow:hidden;">
        @if(hospital_logo_url())
            <img src="{{ hospital_logo_url() }}" alt="{{ hospital_name() }} logo" style="width:100%;height:100%;object-fit:contain;padding:8px;">
        @else
            <span style="font-size:28px;color:#0f4c81">👁</span>
        @endif
    </div>
    <div>
        <h1 style="margin:0 0 6px">Take-Home Medicine Slip</h1>
        <div>Patient: {{ $booking->patient?->full_name ?? '-' }} | Doctor: {{ $booking->otDoctor?->name ?? '-' }}</div>
        <div>Date: {{ optional($booking->discharged_at)->format('d M Y h:i A') ?? now()->format('d M Y h:i A') }}</div>
    </div>
</div>
@endif

<div class="meta">
    <div><strong>Patient Code:</strong> {{ $booking->patient?->patient_code ?? '-' }}</div>
    <div><strong>Location:</strong> {{ $booking->patient?->location?->name ?: '-' }}</div>
</div>

@if(!empty($wardMedicines))
    <ul>
        @foreach($wardMedicines as $medicine)
            <li><strong>{{ $medicine['medicine'] ?? 'Medicine' }}</strong> @if(!empty($medicine['dose'])) - {{ $medicine['dose'] }} @endif</li>
        @endforeach
    </ul>
@else
    <p>No take-home medicines recorded.</p>
@endif
</body>
</html>
