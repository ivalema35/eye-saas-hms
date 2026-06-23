<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Operation Certificate</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #111827; }
        .head { border-bottom: 2px solid #0f4c81; padding-bottom: 8px; margin-bottom: 16px; }
        .head h1 { margin: 0; color: #0f4c81; }
        .line { margin-bottom: 8px; }
        .label { color: #6b7280; }
        .print-btn { position: fixed; right: 12px; top: 12px; }
        @media print { .print-btn { display:none; } body { margin: 0; } }
    </style>
</head>
<body>
<button class="print-btn" onclick="window.print()">Print</button>
@php $letterPad = hospital_setting('letter_pad', 'unavailable'); @endphp
@if($letterPad === 'available')
<div class="head">
    <h1>Operation Certificate</h1>
</div>
@else
<div class="head" style="display:flex;align-items:center;gap:12px;">
    <div style="width:72px;height:72px;border-radius:12px;background:#F8FAFC;border:1px solid #E5E7EB;display:flex;align-items:center;justify-content:center;overflow:hidden;">
        @if(hospital_logo_url())
            <img src="{{ hospital_logo_url() }}" alt="{{ hospital_name() }} logo" style="width:100%;height:100%;object-fit:contain;padding:8px;">
        @else
            <span style="font-size:28px;color:#0f4c81">👁</span>
        @endif
    </div>
    <div>
        <h1>Operation Certificate</h1>
        <div>{{ hospital_name() }} | Tenant: {{ $slug }}</div>
    </div>
</div>
@endif
<div class="line"><span class="label">Patient:</span> {{ $booking->patient?->full_name ?? '-' }}</div>
<div class="line"><span class="label">Patient Code:</span> {{ $booking->patient?->patient_code ?? '-' }}</div>
<div class="line"><span class="label">Doctor:</span> {{ $booking->otDoctor?->name ?? '-' }}</div>
<div class="line"><span class="label">Surgery Performed:</span> {{ $surgery->surgery_name ?? $booking->ot_type ?? '-' }}</div>
<div class="line"><span class="label">Date of Surgery:</span> {{ optional($booking->operated_at)->format('d M Y h:i A') ?? optional($booking->surgery_date)->format('d M Y') }}</div>
<div class="line"><span class="label">Complication Status:</span> {{ strtoupper((string) (optional($surgery)->complication_status ?? 'none')) }}</div>
</body>
</html>
